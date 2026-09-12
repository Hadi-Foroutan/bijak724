<?php

namespace App\Interfaces\Company;

use App\Models\Company\Driver;
use App\Models\Company\ShipmentParty;
use App\Models\Company\Waybill;
use App\Models\TransportContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rules\Exists;

interface WaybillRepositoryInterface extends CompanyModelRepositoryInterface
{
    public function trackingCodeExists(int $companyId, string $trackingCode): bool;

    public function senderExistsRule(int $companyId): Exists;

    public function receiverExistsRule(int $companyId): Exists;

    public function driverExistsRule(int $companyId): Exists;

    public function fleetExistsRule(int $companyId): Exists;

    public function findShipmentPartyOrFail(int $companyId, int $shipmentPartyId): ShipmentParty;

    public function findDriverOrFail(int $companyId, int $driverId): Driver;

    public function findTransportContractOrFail(int $companyId, int $transportContractId): TransportContract;

    /** @return Collection<int, TransportContract> */
    public function transportContractOptions(int $companyId): Collection;

    /** @param array<int, array<string, mixed>> $cargos */
    public function syncCargos(Waybill $waybill, int $companyId, array $cargos): void;
}
