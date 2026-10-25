<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\FleetRepositoryInterface;
use App\Models\Company\Fleet;
use App\Models\FleetBrand;
use App\Models\FleetType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FleetRepository implements FleetRepositoryInterface
{
    public function query(int $companyId): Builder
    {
        return Fleet::queryForCompany($companyId);
    }

    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        $filters['itemsPerPage'] ??= $filters['per_page'] ?? 15;

        return Fleet::searchRecordsForCompany($companyId, $filters);
    }

    public function create(int $companyId, array $data): Fleet
    {
        return Fleet::createForCompany($companyId, $data);
    }

    public function findOrFail(int $companyId, int $id): Fleet
    {
        return Fleet::findForCompanyOrFail($companyId, $id);
    }

    public function update(int $companyId, int $id, array $data): Fleet
    {
        return Fleet::updateForCompany($companyId, $id, $data);
    }

    public function delete(int $companyId, int $id): void
    {
        Fleet::deleteForCompany($companyId, $id);
    }

    public function findByPlate(int $companyId, array $plate): Fleet
    {
        return $this->plateQuery($companyId, $plate)
            ->with(Fleet::defaultRelationsForCompany())
            ->firstOrFail();
    }

    public function plateExists(int $companyId, array $plate, ?int $ignoreFleetId = null): bool
    {
        $query = $this->plateQuery($companyId, $plate);

        if ($ignoreFleetId !== null) {
            $query->whereKeyNot($ignoreFleetId);
        }

        return $query->exists();
    }

    public function systemIdByCode(int $systemCode): ?int
    {
        $systemId = FleetBrand::query()
            ->where('brand_code', $systemCode)
            ->value('id');

        return $systemId === null ? null : (int)$systemId;
    }

    public function tipExists(int $tipCode): bool
    {
        return FleetType::query()
            ->where('tip_code', $tipCode)
            ->exists();
    }

    public function tipBelongsToSystem(int $tipCode, int $systemId): bool
    {
        return FleetType::query()
            ->where('tip_code', $tipCode)
            ->whereHas('brand', fn($query) => $query->whereKey($systemId))
            ->exists();
    }

    private function plateQuery(int $companyId, array $plate): Builder
    {
        // A plate is unique per owning company (a branch may register the same vehicle for itself).
        return Fleet::ownedQueryForCompany($companyId)
            ->where('plate_first_number', $plate['plate_first_number'])
            ->where('plate_second_letter', $plate['plate_second_letter'])
            ->where('plate_third_number', $plate['plate_third_number'])
            ->where('plate_fourth_number', $plate['plate_fourth_number']);
    }
}
