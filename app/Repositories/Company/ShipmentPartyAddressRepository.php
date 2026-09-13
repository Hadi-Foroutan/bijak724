<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\ShipmentPartyAddressRepositoryInterface;
use App\Models\Company\ShipmentParty;
use App\Models\Company\ShipmentPartyAddress;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class ShipmentPartyAddressRepository extends CompanyModelRepository implements ShipmentPartyAddressRepositoryInterface
{
    protected string $tableKey = 'shipment_party_addresses';

    public function searchForParty(
        int $companyId,
        int $shipmentPartyId,
        array $filters,
    ): Collection|LengthAwarePaginator {
        $filters['eq-shipment_party_id'] = $shipmentPartyId;

        return $this->search($companyId, $filters);
    }

    public function shipmentPartyExists(int $companyId, int $shipmentPartyId): bool
    {
        return $this->tableRegistry->query($companyId, 'shipment_parties')
            ->whereKey($shipmentPartyId)
            ->exists();
    }

    public function uniquePostalCodeForPartyRule(
        int $companyId,
        int $shipmentPartyId,
        ?int $ignoreAddressId = null,
    ): Unique {
        $rule = Rule::unique(
            $this->tableRegistry->tableName($companyId, $this->tableKey),
            'postal_code',
        )->where('shipment_party_id', $shipmentPartyId);

        return $ignoreAddressId === null ? $rule : $rule->ignore($ignoreAddressId);
    }

    public function findForPartyOrFail(
        int $companyId,
        int $shipmentPartyId,
        int $addressId,
    ): ShipmentPartyAddress {
        /** @var ShipmentPartyAddress $address */
        $address = $this->query($companyId)
            ->where('shipment_party_id', $shipmentPartyId)
            ->findOrFail($addressId);

        /** @var ShipmentPartyAddress */
        return $this->loadRelations($address);
    }

    public function findShipmentPartyByPostalCodeAndType(
        int $companyId,
        string $postalCode,
        string $type,
    ): ?ShipmentParty {
        $roleColumn = $type === 'sender' ? 'is_sender' : 'is_receiver';

        /** @var ShipmentPartyAddress|null $address */
        $address = $this->query($companyId)
            ->where('postal_code', $postalCode)
            ->whereHas(
                'shipmentParty',
                fn (Builder $query): Builder => $query->where($roleColumn, true),
            )
            ->first();

        if ($address === null) {
            return null;
        }

        /** @var ShipmentParty|null $shipmentParty */
        $shipmentParty = $address->shipmentParty;

        /** @var ShipmentParty|null */
        return $shipmentParty === null ? null : $this->loadRelations($shipmentParty);
    }

    public function updateForParty(
        int $companyId,
        int $shipmentPartyId,
        int $addressId,
        array $data,
    ): ShipmentPartyAddress {
        $address = $this->findForPartyOrFail($companyId, $shipmentPartyId, $addressId);
        $address->update($data);

        /** @var ShipmentPartyAddress */
        return $this->loadRelations($address->refresh());
    }

    public function deleteForParty(int $companyId, int $shipmentPartyId, int $addressId): void
    {
        $this->findForPartyOrFail($companyId, $shipmentPartyId, $addressId)->delete();
    }
}
