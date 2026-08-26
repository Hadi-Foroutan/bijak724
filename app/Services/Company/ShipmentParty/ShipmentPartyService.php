<?php

namespace App\Services\Company\ShipmentParty;

use App\Enums\StatusEnum;
use App\Interfaces\Company\ShipmentPartyRepositoryInterface;
use App\Services\Company\CompanyCrudService;

class ShipmentPartyService extends CompanyCrudService
{
    protected string $resourceLabel = 'فرستنده/گیرنده';

    public function __construct(ShipmentPartyRepositoryInterface $shipmentPartyRepository)
    {
        parent::__construct($shipmentPartyRepository);
    }

    protected function prepareCreateData(array $data): array
    {
        $data['status'] ??= StatusEnum::ACTIVE->value;

        return $data;
    }
}
