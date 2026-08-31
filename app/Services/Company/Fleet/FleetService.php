<?php

namespace App\Services\Company\Fleet;

use App\Enums\StatusEnum;
use App\Helpers\ServiceResult;
use App\Interfaces\Company\FleetRepositoryInterface;
use App\Services\Company\CompanyCrudService;
use Illuminate\Validation\ValidationException;

class FleetService extends CompanyCrudService
{
    protected string $resourceLabel = 'ناوگان';

    public function __construct(
        protected FleetRepositoryInterface $fleetRepository,
    ) {
        parent::__construct($fleetRepository);
    }

    public function findBySmartCardNumber(int $companyId, string $smartCardNumber): ServiceResult
    {
        return ServiceResult::success(
            $this->fleetRepository->findBySmartCardNumber($companyId, $smartCardNumber),
        );
    }

    protected function prepareCreateData(array $data): array
    {
        $data['status'] ??= StatusEnum::ACTIVE->value;
        $data['has_violation'] ??= false;
        $data['system_id'] = $this->resolveSystemId((int) $data['tip_code']);

        return $data;
    }

    protected function prepareUpdateData(array $data): array
    {
        if (array_key_exists('tip_code', $data)) {
            $data['system_id'] = $this->resolveSystemId((int) $data['tip_code']);
        }

        return $data;
    }

    private function resolveSystemId(int $tipCode): int
    {
        $systemId = $this->fleetRepository->systemIdForTipCode($tipCode);

        if ($systemId === null) {
            throw ValidationException::withMessages([
                'tip_code' => 'برند مرتبط با تیپ انتخاب‌شده معتبر نیست.',
            ]);
        }

        return $systemId;
    }
}
