<?php

namespace App\Services\Company\Sms;

use App\Helpers\ServiceResult;
use App\Interfaces\Company\WaybillRepositoryInterface;
use App\Models\User;
use App\Services\Sms\SmsService;
use App\Support\IranianMobile;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

class DriverBroadcastService
{
    private const DRIVER_SLOTS = ['driver1', 'driver2', 'referral_driver'];

    public function __construct(
        protected WaybillRepositoryInterface $waybillRepository,
        protected SmsService                 $smsService,
    )
    {
    }

    /**
     * The list the user reviews before sending.
     *
     * @param array<string, mixed> $filters
     */
    public function drivers(int $companyId, array $filters, User $user): ServiceResult
    {
        $reason = $this->smsService->unavailableReason($companyId, $user);

        return ServiceResult::success([
            'available' => $reason === null,
            'reason' => $reason,
            ...$this->matchingDrivers($companyId, $filters, $user),
        ]);
    }

    /**
     * The list is built again here, not taken from the client, so only drivers of the route can receive the text.
     *
     * @param array<string, mixed> $data the filters, `text` and `excluded_phones`
     */
    public function send(int $companyId, array $data, User $user): ServiceResult
    {
        $this->smsService->ensureCanSend($companyId, $user);

        $phones = array_values(array_diff(
            array_column($this->matchingDrivers($companyId, $data, $user)['drivers'], 'phone'),
            IranianMobile::unique($data['excluded_phones'] ?? []),
        ));

        if ($phones === []) {
            ServiceResult::error(__('public.sms_no_recipients'), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $result = $this->smsService->send($companyId, $user, $phones, $data['text'], 'ارسال گروهی به رانندگان');

        if (!$result['success']) {
            ServiceResult::error($result['message'], Response::HTTP_BAD_GATEWAY);
        }

        return ServiceResult::success($result);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{drivers: list<array{phone: string, full_name: ?string, national_code: ?string, trips_count: int}>, without_valid_phone: int}
     */
    private function matchingDrivers(int $companyId, array $filters, User $user): array
    {
        $waybills = $this->waybillRepository->issuedOnRoute(
            $companyId,
            (int)$filters['origin_city_code'],
            (int)$filters['destination_city_code'],
            Carbon::parse($filters['date_from'])->startOfDay(),
            Carbon::parse($filters['date_to'])->endOfDay(),
            $user,
        );
        $drivers = [];
        $withoutPhone = [];

        foreach ($waybills as $waybill) {
            $countedOnThisWaybill = [];

            foreach (self::DRIVER_SLOTS as $slot) {
                $nationalCode = $waybill->{"{$slot}_national_code"};
                $phone = IranianMobile::normalize($waybill->{"{$slot}_phone"});

                if ($phone === null) {
                    if (filled($nationalCode)) {
                        $withoutPhone[$nationalCode] = true;
                    }

                    continue;
                }

                $drivers[$phone] ??= [
                    'phone' => $phone,
                    'full_name' => $waybill->{"{$slot}_full_name"},
                    'national_code' => $nationalCode,
                    'trips_count' => 0,
                ];

                if (!isset($countedOnThisWaybill[$phone])) {
                    $drivers[$phone]['trips_count']++;
                    $countedOnThisWaybill[$phone] = true;
                }
            }
        }

        return [
            'drivers' => array_values($drivers),
            'without_valid_phone' => count(array_diff_key(
                $withoutPhone,
                array_flip(array_filter(array_column($drivers, 'national_code'))),
            )),
        ];
    }
}
