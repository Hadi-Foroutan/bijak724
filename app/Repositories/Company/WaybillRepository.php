<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\WaybillRepositoryInterface;
use App\Models\Company\Driver;
use App\Models\Company\ShipmentParty;
use App\Models\Company\Waybill;
use App\Models\TransportContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Exists;

class WaybillRepository extends CompanyModelRepository implements WaybillRepositoryInterface
{
    private const CARGO_FIELDS = [
        'cargo_id',
        'packaging_id',
        'title',
        'origin_weight',
        'value',
        'quantity',
        'is_traffic',
        'is_returned',
        'cottage_number',
        'cottage_number_2',
        'driver_account_number',
        'container_number',
        'container_number_2',
    ];

    protected string $tableKey = 'waybills';

    public function trackingCodeExists(int $companyId, string $trackingCode): bool
    {
        return $this->tableRegistry->sharedQuery($companyId, $this->tableKey)
            ->where('bijak_tracking_code', $trackingCode)
            ->exists();
    }

    public function senderExistsRule(int $companyId): Exists
    {
        return $this->tableRegistry->ownedExistsRule($companyId, 'shipment_parties')
            ->where('is_sender', true);
    }

    public function receiverExistsRule(int $companyId): Exists
    {
        return $this->tableRegistry->ownedExistsRule($companyId, 'shipment_parties')
            ->where('is_receiver', true);
    }

    public function driverExistsRule(int $companyId): Exists
    {
        return $this->tableRegistry->ownedExistsRule($companyId, 'drivers');
    }

    public function fleetExistsRule(int $companyId): Exists
    {
        return $this->tableRegistry->ownedExistsRule($companyId, 'fleets');
    }

    public function findShipmentPartyOrFail(int $companyId, int $shipmentPartyId): ShipmentParty
    {
        /** @var ShipmentParty $shipmentParty */
        $shipmentParty = $this->tableRegistry->query($companyId, 'shipment_parties')
            ->findOrFail($shipmentPartyId);

        return $shipmentParty;
    }

    public function findDriverOrFail(int $companyId, int $driverId): Driver
    {
        /** @var Driver $driver */
        $driver = $this->tableRegistry->query($companyId, 'drivers')->findOrFail($driverId);

        return $driver;
    }

    public function findTransportContractOrFail(
        int $companyId,
        int $transportContractId,
    ): TransportContract {
        return TransportContract::query()
            ->where('company_id', $companyId)
            ->with('items')
            ->findOrFail($transportContractId);
    }

    /** @return Collection<int, TransportContract> */
    public function transportContractOptions(int $companyId): Collection
    {
        return TransportContract::query()
            ->where('company_id', $companyId)
            ->with('items')
            ->orderBy('title')
            ->get();
    }

    public function update(int $companyId, int $id, array $data): Waybill
    {
        unset($data['owner_company_id']);

        /** @var Waybill $waybill */
        $waybill = $this->findOrFail($companyId, $id);
        $waybill->fill($data);

        if ($waybill->isDirty()) {
            $waybill->save();
        }

        /** @var Waybill $waybill */
        $waybill = $this->loadRelations($waybill->refresh());

        return $waybill;
    }

    public function syncCargos(Waybill $waybill, int $companyId, array $cargos): void
    {
        if ($this->hasSameCargos($waybill, $cargos)) {
            return;
        }

        $waybill->cargos()->delete();

        if ($cargos === []) {
            return;
        }

        $waybill->cargos()->createMany(array_map(
            fn (array $cargo): array => [...$cargo, 'owner_company_id' => $companyId],
            $cargos,
        ));
    }

    /** @param array<int, array<string, mixed>> $cargos */
    private function hasSameCargos(Waybill $waybill, array $cargos): bool
    {
        $waybill->loadMissing('cargos');

        $currentCargos = $waybill->cargos
            ->map(fn ($cargo): array => $this->normalizeCargo($cargo->only(self::CARGO_FIELDS)))
            ->values()
            ->all();
        $newCargos = array_map(fn (array $cargo): array => $this->normalizeCargo($cargo), $cargos);

        return $currentCargos === $newCargos;
    }

    /** @param array<string, mixed> $cargo */
    private function normalizeCargo(array $cargo): array
    {
        $normalized = array_replace(
            array_fill_keys(self::CARGO_FIELDS, null),
            Arr::only($cargo, self::CARGO_FIELDS),
        );

        foreach (['cargo_id', 'packaging_id', 'value', 'quantity'] as $field) {
            $normalized[$field] = $normalized[$field] === null ? null : (int) $normalized[$field];
        }

        $normalized['origin_weight'] = $normalized['origin_weight'] === null
            ? null
            : (float) $normalized['origin_weight'];
        $normalized['is_traffic'] = (bool) $normalized['is_traffic'];
        $normalized['is_returned'] = (bool) $normalized['is_returned'];

        return $normalized;
    }
}
