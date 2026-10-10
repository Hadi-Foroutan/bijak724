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
    public function query(int $companyId): Builder
    {
        return ShipmentPartyAddress::queryForCompany($companyId);
    }

    public function create(int $companyId, array $data): ShipmentPartyAddress
    {
        return ShipmentPartyAddress::createForCompany($companyId, $data);
    }

    public function existsRule(int $companyId, string $column = 'id'): Exists
    {
        $model = ShipmentPartyAddress::modelForCompany($companyId);
        $rule = Rule::exists($model->getTable(), $column);

        return $model->companyId() === $companyId
            ? $rule
            : $rule->where('owner_company_id', $companyId);
    }

    public function findOrFail(int $companyId, int $addressId): ShipmentPartyAddress
    {
        return ShipmentPartyAddress::findForCompanyOrFail($companyId, $addressId);
    }

    public function searchForParty(
        int $companyId,
        int $shipmentPartyId,
        array $filters,
    ): Collection|LengthAwarePaginator {
        return ShipmentPartyAddress::searchRecordsForCompany(
            $companyId,
            $filters,
            fn (Builder $query): Builder => $query
                ->where('shipment_party_id', $shipmentPartyId),
        );
    }

    public function uniquePostalCodeForPartyRule(
        int $companyId,
        int $shipmentPartyId,
        ?int $ignoreAddressId = null,
    ): Unique {
        $table = ShipmentPartyAddress::modelForCompany($companyId)->getTable();
        $rule = Rule::unique($table, 'postal_code')
            ->where('shipment_party_id', $shipmentPartyId);

        return $ignoreAddressId === null ? $rule : $rule->ignore($ignoreAddressId);
    }

    public function findForPartyOrFail(
        int $companyId,
        int $shipmentPartyId,
        int $addressId,
    ): ShipmentPartyAddress {
        return ShipmentPartyAddress::findForCompanyOrFail(
            $companyId,
            $addressId,
            fn (Builder $query): Builder => $query->where('shipment_party_id', $shipmentPartyId),
        );
    }

    public function findShipmentPartyByPostalCodeAndType(
        int $companyId,
        string $postalCode,
        string $type,
    ): ?ShipmentParty {
        $roleColumn = $type === 'sender' ? 'is_sender' : 'is_receiver';

        $address = ShipmentPartyAddress::queryForCompany($companyId)
            ->with(ShipmentPartyAddress::defaultRelationsForCompany())
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
        unset($data['shipment_party_id']);

        return ShipmentPartyAddress::updateForCompany(
            $companyId,
            $addressId,
            $data,
            fn (Builder $query): Builder => $query->where('shipment_party_id', $shipmentPartyId),
        );
    }

    public function deleteForParty(int $companyId, int $shipmentPartyId, int $addressId): void
    {
        ShipmentPartyAddress::deleteForCompany(
            $companyId,
            $addressId,
            fn (Builder $query): Builder => $query->where('shipment_party_id', $shipmentPartyId),
        );
    }
}
