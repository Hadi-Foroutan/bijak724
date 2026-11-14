<?php

namespace App\Services\Company\Sms;

use App\Enums\StatusEnum;
use App\Helpers\ServiceResult;
use App\Interfaces\Company\SmsPanelRepositoryInterface;
use App\Models\Company;
use App\Models\SmsPanel;
use App\Models\User;
use App\Services\Sms\SmsService;
use App\Support\IranianMobile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SmsPanelService
{
    public function __construct(
        protected SmsPanelRepositoryInterface $smsPanelRepository,
        protected SmsService                  $smsService,
    )
    {
    }

    public function show(int $companyId, User $user): ServiceResult
    {
        return ServiceResult::success($this->status(
            $this->smsPanelRepository->findForCompany($companyId),
            $user,
        ));
    }

    public function register(Company $company, User $user): ServiceResult
    {
        return DB::transaction(function () use ($company, $user): ServiceResult {
            // Held while the provider answers: two clicks must not open two accounts there.
            Company::query()->whereKey($company->getKey())->lockForUpdate()->firstOrFail();

            if ($this->smsPanelRepository->findForCompany((int)$company->getKey()) !== null) {
                ServiceResult::error(__('public.sms_panel_already_active'), Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $response = $this->smsService->register($this->registrationData($company, $user));

            Log::info('peykad key', $response);

            $apiKey = data_get($response, 'data.developer.api_key');

            if (!is_string($apiKey) || $apiKey === '') {
                ServiceResult::error(__('public.sms_provider_failed'), Response::HTTP_BAD_GATEWAY);
            }

            $panel = $this->smsPanelRepository->create([
                'company_id' => $company->getKey(),
                'registered_by' => $user->getKey(),
                'provider_user_id' => data_get($response, 'data.user.id'),
                'provider_panel_id' => data_get($response, 'data.panel.id'),
                'status' => StatusEnum::ACTIVE->value,
            ]);
            $this->smsPanelRepository->saveApiKey($panel, (int)$user->getKey(), $apiKey);

            $balance = data_get($response, 'data.panel.wallet.balance');

            if (is_numeric($balance)) {
                $this->smsPanelRepository->updateWalletBalance($panel, $balance);
            }

            return ServiceResult::success($this->status($panel, $user), Response::HTTP_CREATED);
        });
    }

    public function refreshWallet(int $companyId, User $user): ServiceResult
    {
        return ServiceResult::success($this->status($this->smsService->refreshWallet($companyId, $user), $user));
    }

    public function saveToken(int $companyId, User $user, string $apiKey): ServiceResult
    {
        $panel = $this->smsService->activePanel($companyId);

        if ($panel === null) {
            ServiceResult::error(__('public.sms_panel_not_active'), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->smsPanelRepository->saveApiKey($panel, (int)$user->getKey(), $apiKey);

        return ServiceResult::success($this->status($panel, $user));
    }

    /** @return array<string, mixed> */
    private function status(?SmsPanel $panel, User $user): array
    {
        $hasToken = $panel !== null
            && filled($this->smsPanelRepository->apiKey($panel, (int)$user->getKey()));

        return [
            'is_active' => (bool)$panel?->isActive(),
            'activated_at' => $panel?->created_at,
            'token_status' => $hasToken ? 'registered' : 'not_registered',
            'wallet_balance' => $panel?->wallet_balance === null ? null : (float)$panel->wallet_balance,
            'wallet_updated_at' => $panel?->wallet_updated_at,
        ];
    }

    /** @return array<string, mixed> empty values are left out, as the provider asks */
    private function registrationData(Company $company, User $user): array
    {
        $phone = IranianMobile::normalize($user->phone);

        if ($phone === null) {
            ServiceResult::error(__('public.sms_registration_phone_missing'), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return array_filter([
            'phone' => $phone,
            'type' => config('sms.registration.type'),
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'address' => $user->address,
            'national_id' => $user->national_code,
            'company_name' => $company->name,
            'company_phone' => $company->tel,
            'company_email' => $company->email,
            'company_address' => $company->address,
            'company_national_id' => $company->national_code,
        ], filled(...));
    }
}
