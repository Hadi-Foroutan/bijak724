<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\ShipmentPartyRepositoryInterface;
use App\Models\Company\ShipmentParty;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

class ShipmentPartyRepository implements ShipmentPartyRepositoryInterface
{
    public function __construct(protected ShipmentParty $shipmentParty) {}

    public function query(int $companyId): Builder
    {
        return $this->shipmentParty->newQueryForCompany($companyId);
    }

    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        $filters['itemsPerPage'] ??= $filters['per_page'] ?? 15;
        $query = $this->query($companyId)
            ->with($this->shipmentParty->defaultRelations())
            ->advancedSearch($filters);

        return $query->getModel()->advancedSearchResults($query, $filters);
    }

    public function create(int $companyId, array $data): ShipmentParty
    {
        $shipmentParty = $this->shipmentParty
            ->newInstanceForCompany($companyId)
            ->newQuery()
            ->create([...$data, 'owner_company_id' => $companyId]);

        return $shipmentParty->loadDefaultRelations();
    }

    public function findOrFail(int $companyId, int $id): ShipmentParty
    {
        return $this->query($companyId)
            ->with($this->shipmentParty->defaultRelations())
            ->findOrFail($id);
    }

    public function update(int $companyId, int $id, array $data): ShipmentParty
    {
        unset($data['owner_company_id']);

        $shipmentParty = $this->findOrFail($companyId, $id);
        $shipmentParty->update($data);

        return $shipmentParty->refresh()->loadDefaultRelations();
    }

    public function delete(int $companyId, int $id): void
    {
        $this->findOrFail($companyId, $id)->delete();
    }

    public function exists(int $companyId, int $id): bool
    {
        return $this->query($companyId)->whereKey($id)->exists();
    }

    public function existsRule(int $companyId, string $column = 'id'): Exists
    {
        $model = $this->shipmentParty->newInstanceForCompany($companyId);
        $rule = Rule::exists($model->getTable(), $column);

        return $model->companyId() === $companyId
            ? $rule
            : $rule->where('owner_company_id', $companyId);
    }

    public function findByNationalIdentifierAndType(
        int $companyId,
        string $nationalIdentifier,
        string $type,
    ): ShipmentParty {
        $roleColumn = $type === 'sender' ? 'is_sender' : 'is_receiver';

        return $this->query($companyId)
            ->with($this->shipmentParty->defaultRelations())
            ->where('national_identifier', $nationalIdentifier)
            ->where($roleColumn, true)
            ->firstOrFail();
    }

    public function uniqueNationalIdentifierRule(
        int $companyId,
        ?int $ignoreShipmentPartyId = null,
    ): Unique {
        $table = $this->shipmentParty->newInstanceForCompany($companyId)->getTable();
        $rule = Rule::unique($table, 'national_identifier');

        return $ignoreShipmentPartyId === null ? $rule : $rule->ignore($ignoreShipmentPartyId);
    }
}
