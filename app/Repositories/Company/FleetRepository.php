<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\FleetRepositoryInterface;
use App\Models\Company\Fleet;
use App\Models\FleetBrand;
use App\Models\FleetType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

class FleetRepository implements FleetRepositoryInterface
{
    public function __construct(protected Fleet $fleet) {}

    public function query(int $companyId): Builder
    {
        return $this->fleet->newQueryForCompany($companyId);
    }

    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        $filters['itemsPerPage'] ??= $filters['per_page'] ?? 15;
        $query = $this->query($companyId)
            ->with($this->fleet->defaultRelations())
            ->advancedSearch($filters);

        return $query->getModel()->advancedSearchResults($query, $filters);
    }

    public function create(int $companyId, array $data): Fleet
    {
        $fleet = $this->fleet
            ->newInstanceForCompany($companyId)
            ->newQuery()
            ->create([...$data, 'owner_company_id' => $companyId]);

        return $fleet->loadDefaultRelations();
    }

    public function findOrFail(int $companyId, int $id): Fleet
    {
        return $this->query($companyId)
            ->with($this->fleet->defaultRelations())
            ->findOrFail($id);
    }

    public function update(int $companyId, int $id, array $data): Fleet
    {
        unset($data['owner_company_id']);

        $fleet = $this->findOrFail($companyId, $id);
        $fleet->update($data);

        return $fleet->refresh()->loadDefaultRelations();
    }

    public function delete(int $companyId, int $id): void
    {
        $this->findOrFail($companyId, $id)->delete();
    }

    public function existsRule(int $companyId, string $column = 'id'): Exists
    {
        $model = $this->fleet->newInstanceForCompany($companyId);
        $rule = Rule::exists($model->getTable(), $column);

        return $model->companyId() === $companyId
            ? $rule
            : $rule->where('owner_company_id', $companyId);
    }

    public function findByPlate(int $companyId, array $plate): Fleet
    {
        return $this->plateQuery($companyId, $plate)
            ->with($this->fleet->defaultRelations())
            ->firstOrFail();
    }

    public function plateExists(int $companyId, array $plate, ?int $ignoreFleetId = null): bool
    {
        $query = $this->plateQuery($companyId, $plate, true);

        if ($ignoreFleetId !== null) {
            $query->whereKeyNot($ignoreFleetId);
        }

        return $query->exists();
    }

    public function uniqueSmartCardNumberRule(int $companyId, ?int $ignoreFleetId = null): Unique
    {
        $table = $this->fleet->newInstanceForCompany($companyId)->getTable();
        $rule = Rule::unique($table, 'smart_card_number');

        return $ignoreFleetId === null ? $rule : $rule->ignore($ignoreFleetId);
    }

    public function systemIdByCode(int $systemCode): ?int
    {
        $systemId = FleetBrand::query()
            ->where('brand_code', $systemCode)
            ->value('id');

        return $systemId === null ? null : (int) $systemId;
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
            ->whereHas('brand', fn ($query) => $query->whereKey($systemId))
            ->exists();
    }

    private function plateQuery(int $companyId, array $plate, bool $shared = false): Builder
    {
        $query = $shared
            ? $this->fleet->newSharedQueryForCompany($companyId)
            : $this->query($companyId);

        return $query
            ->where('plate_first_number', $plate['plate_first_number'])
            ->where('plate_second_letter', $plate['plate_second_letter'])
            ->where('plate_third_number', $plate['plate_third_number'])
            ->where('plate_fourth_number', $plate['plate_fourth_number']);
    }
}
