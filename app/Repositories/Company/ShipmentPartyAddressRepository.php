<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\ShipmentPartyAddressRepositoryInterface;
use App\Models\Company\ShipmentParty;
use App\Models\Company\ShipmentPartyAddress;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

class ShipmentPartyAddressRepository implements ShipmentPartyAddressRepositoryInterface
{
    public function __construct(protected ShipmentPartyAddress $shipmentPartyAddress) {}

    public function query(int $companyId): Builder
    {
        return $this->shipmentPartyAddress->newQueryForCompany($companyId);
    }

    public function create(int $companyId, array $data): ShipmentPartyAddress
    {
        $address = $this->shipmentPartyAddress
            ->newInstanceForCompany($companyId)
            ->newQuery()
            ->create([...$data, 'owner_company_id' => $companyId]);

        return $address->loadDefaultRelations();
    }

    public function existsRule(int $companyId, string $column = 'id'): Exists
    {
        $model = $this->shipmentPartyAddress->newInstanceForCompany($companyId);
        $rule = Rule::exists($model->getTable(), $column);

        return $model->companyId() === $companyId
            ? $rule
            : $rule->where('owner_company_id', $companyId);
    }

    public function findOrFail(int $companyId, int $addressId): ShipmentPartyAddress
    {
        return $this->query($companyId)
            ->with($this->shipmentPartyAddress->defaultRelations())
            ->findOrFail($addressId);
    }

    public function searchForParty(
        int $companyId,
        int $shipmentPartyId,
        array $filters,
    ): Collection|LengthAwarePaginator {
        $filters['itemsPerPage'] ??= $filters['per_page'] ?? 15;
        $filters['eq-shipment_party_id'] = $shipmentPartyId;
        $query = $this->query($companyId)
            ->with($this->shipmentPartyAddress->defaultRelations())
            ->advancedSearch($filters);

        return $query->getModel()->advancedSearchResults($query, $filters);
    }

    public function uniquePostalCodeForPartyRule(
        int $companyId,
        int $shipmentPartyId,
        ?int $ignoreAddressId = null,
    ): Unique {
        $table = $this->shipmentPartyAddress->newInstanceForCompany($companyId)->getTable();
        $rule = Rule::unique($table, 'postal_code')
            ->where('shipment_party_id', $shipmentPartyId);

        return $ignoreAddressId === null ? $rule : $rule->ignore($ignoreAddressId);
    }

    public function findForPartyOrFail(
        int $companyId,
        int $shipmentPartyId,
        int $addressId,
    ): ShipmentPartyAddress {
        return $this->query($companyId)
            ->with($this->shipmentPartyAddress->defaultRelations())
            ->where('shipment_party_id', $shipmentPartyId)
            ->findOrFail($addressId);
    }

    public function findShipmentPartyByPostalCodeAndType(
        int $companyId,
        string $postalCode,
        string $type,
    ): ?ShipmentParty {
        $roleColumn = $type === 'sender' ? 'is_sender' : 'is_receiver';

        $address = $this->query($companyId)
            ->with($this->shipmentPartyAddress->defaultRelations())
            ->where('postal_code', $postalCode)
            ->whereHas(
                'shipmentParty',
                fn (Builder $query): Builder => $query->where($roleColumn, true),
            )
            ->first();

        return $address?->shipmentParty?->loadDefaultRelations();
    }

    public function updateForParty(
        int $companyId,
        int $shipmentPartyId,
        int $addressId,
        array $data,
    ): ShipmentPartyAddress {
        unset($data['owner_company_id'], $data['shipment_party_id']);

        $address = $this->findForPartyOrFail($companyId, $shipmentPartyId, $addressId);
        $address->update($data);

        return $address->refresh()->loadDefaultRelations();
    }

    public function deleteForParty(int $companyId, int $shipmentPartyId, int $addressId): void
    {
        $this->findForPartyOrFail($companyId, $shipmentPartyId, $addressId)->delete();
    }
}
