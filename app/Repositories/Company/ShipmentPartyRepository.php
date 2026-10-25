<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\ShipmentPartyRepositoryInterface;
use App\Models\Company\ShipmentParty;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ShipmentPartyRepository implements ShipmentPartyRepositoryInterface
{
    public function query(int $companyId): Builder
    {
        return ShipmentParty::queryForCompany($companyId);
    }

    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        $filters['itemsPerPage'] ??= $filters['per_page'] ?? 15;

        return ShipmentParty::searchRecordsForCompany($companyId, $filters);
    }

    public function create(int $companyId, array $data): ShipmentParty
    {
        return ShipmentParty::createForCompany($companyId, $data);
    }

    public function findOrFail(int $companyId, int $id): ShipmentParty
    {
        return ShipmentParty::findForCompanyOrFail($companyId, $id);
    }

    public function update(int $companyId, int $id, array $data): ShipmentParty
    {
        return ShipmentParty::updateForCompany($companyId, $id, $data);
    }

    public function delete(int $companyId, int $id): void
    {
        ShipmentParty::deleteForCompany($companyId, $id);
    }

    public function exists(int $companyId, int $id): bool
    {
        return ShipmentParty::queryForCompany($companyId)->whereKey($id)->exists();
    }

    public function findByNationalIdentifierAndType(
        int    $companyId,
        string $nationalIdentifier,
        string $type,
    ): ShipmentParty
    {
        $roleColumn = $type === 'sender' ? 'is_sender' : 'is_receiver';

        return ShipmentParty::queryPreferringOwnRows($companyId)
            ->with(ShipmentParty::defaultRelationsForCompany())
            ->where('national_identifier', $nationalIdentifier)
            ->where($roleColumn, true)
            ->firstOrFail();
    }

}
