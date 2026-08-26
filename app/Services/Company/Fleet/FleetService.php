<?php

namespace App\Services\Company\Fleet;

use App\Enums\StatusEnum;
use App\Helpers\ServiceResult;
use App\Interfaces\Company\FleetRepositoryInterface;
use App\Services\Company\CompanyCrudService;

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

        return $data;
    }

    protected function prepareUpdateData(array $data): array
    {
        if (array_key_exists('fleet_brand_id', $data)
            && ! array_key_exists('fleet_type_code', $data)) {
            $data['fleet_type_code'] = null;
        }

        return $data;
    }
}
