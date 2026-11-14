<?php

namespace App\Services\Sms;

use AlirezaMalekei\PeykadSms\Exceptions\SmsException;
use AlirezaMalekei\PeykadSms\Facades\Panel;
use AlirezaMalekei\PeykadSms\Facades\Sms;
use AlirezaMalekei\PeykadSms\Facades\Wallet;
use App\Enums\GlobalSettingKey;
use App\Helpers\ServiceResult;
use App\Interfaces\Company\SmsPanelRepositoryInterface;
use App\Models\SmsPanel;
use App\Models\User;
use App\Services\Setting\GlobalSettingService;
use App\Support\IranianMobile;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * The only door to the SMS provider. A message always goes out with the access key of the user who sends it, read here,
 * so no caller can pass someone else's key, and always to a list with every number once.
 */
class SmsService
{
    public function __construct(
        protected SmsPanelRepositoryInterface $smsPanelRepository,
        protected GlobalSettingService        $globalSettingService,
    )
    {
    }

    /** Why this user cannot send right now, or null when they can. */
    public function unavailableReason(int $companyId, User $user): ?string
    {
        $panel = $this->activePanel($companyId);

        if ($panel === null) {
            return __('public.sms_panel_not_active');
        }

        return blank($this->smsPanelRepository->apiKey($panel, (int)$user->getKey()))
            ? __('public.sms_access_key_missing')
            : null;
    }

    public function ensureCanSend(int $companyId, User $user): void
    {
        $reason = $this->unavailableReason($companyId, $user);

        if ($reason !== null) {
            ServiceResult::error($reason, Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function activePanel(int $companyId): ?SmsPanel
    {
        $panel = $this->smsPanelRepository->findForCompany($companyId);

        return $panel?->isActive() ? $panel : null;
    }

    /**
     * Sends one text to a list. A provider failure is part of the result rather than an exception, so a send to several
     * parties can report each one.
     *
     * @param iterable<?string> $phones
     * @return array{success: bool, phones: list<string>, tracking_code: ?string, wallet_balance: ?float, message: string}
     */
    public function send(int $companyId, User $user, iterable $phones, string $text, string $campaignName): array
    {
        $this->ensureCanSend($companyId, $user);

        $panel = $this->activePanel($companyId);
        $phones = IranianMobile::unique($phones);

        try {
            $response = Sms::panel($this->credentials($panel, $user))
                ->send($phones, $text, ['name' => $campaignName]);
        } catch (SmsException|ConnectionException $exception) {
            Log::warning('SMS send failed', ['company_id' => $companyId, 'error' => $exception->getMessage()]);

            return [
                'success' => false,
                'phones' => $phones,
                'tracking_code' => null,
                'wallet_balance' => null,
                'message' => __('public.sms_provider_failed'),
            ];
        }

        $balance = $response->balance();

        if (is_numeric($balance)) {
            $this->smsPanelRepository->updateWalletBalance($panel, $balance);
        }

        return [
            'success' => true,
            'phones' => $phones,
            'tracking_code' => $response->trackingCode(),
            'wallet_balance' => is_numeric($balance) ? (float)$balance : null,
            'message' => __('public.sms_sent_success'),
        ];
    }

    /** Asks the provider for the wallet balance and stores it on the panel. */
    public function refreshWallet(int $companyId, User $user): SmsPanel
    {
        $this->ensureCanSend($companyId, $user);

        $panel = $this->activePanel($companyId);

        try {
            $balance = Wallet::get($this->credentials($panel, $user))->balance();
        } catch (SmsException $exception) {
            Log::warning('SMS wallet refresh failed', ['company_id' => $companyId, 'error' => $exception->getMessage()]);

            ServiceResult::error(__('public.sms_provider_failed'), Response::HTTP_BAD_GATEWAY);
        }

        if ($balance === null) {
            ServiceResult::error(__('public.sms_provider_failed'), Response::HTTP_BAD_GATEWAY);
        }

        $this->smsPanelRepository->updateWalletBalance($panel, $balance);

        return $panel;
    }

    /**
     * A 422 from the provider is left to reach the client: it carries the field errors of the submitted data.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed> the provider's whole response
     */
    public function register(array $data): array
    {
        try {
            return Panel::register($data, array_filter(['base_url' => $this->panelUrl()]))->toArray();
        } catch (SmsException|ConnectionException $exception) {
            Log::warning('SMS panel registration failed', ['error' => $exception->getMessage()]);

            ServiceResult::error(__('public.sms_provider_failed'), Response::HTTP_BAD_GATEWAY);
        }
    }

    /** @return array<string, string> the provider overrides that make a call run as this user */
    private function credentials(SmsPanel $panel, User $user): array
    {
        return array_filter([
            'api_key' => (string)$this->smsPanelRepository->apiKey($panel, (int)$user->getKey()),
            'base_url' => $this->panelUrl(),
        ]);
    }

    private function panelUrl(): ?string
    {
        $url = $this->globalSettingService->value(GlobalSettingKey::SmsPanelUrl);

        return is_string($url) && $url !== '' ? $url : null;
    }
}
