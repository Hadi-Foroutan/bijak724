<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\WaybillRepositoryInterface;
use App\Models\Company\Waybill;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class WaybillRepository implements WaybillRepositoryInterface
{
    public function __construct(protected Waybill $waybill) {}

    public function query(int $companyId): Builder
    {
        return $this->waybill->newQueryForCompany($companyId);
    }

    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        $filters['itemsPerPage'] ??= $filters['per_page'] ?? 15;
        $query = $this->query($companyId)
            ->with($this->waybill->defaultRelations())
            ->advancedSearch($filters);

        return $query->getModel()->advancedSearchResults($query, $filters);
    }

    public function create(int $companyId, array $data): Waybill
    {
        $waybill = $this->waybill
            ->newInstanceForCompany($companyId)
            ->newQuery()
            ->create([...$data, 'owner_company_id' => $companyId]);

        return $waybill->loadDefaultRelations();
    }

    public function findOrFail(int $companyId, int $id): Waybill
    {
        return $this->query($companyId)
            ->with($this->waybill->defaultRelations())
            ->findOrFail($id);
    }

    public function update(int $companyId, int $id, array $data): Waybill
    {
        unset($data['owner_company_id']);

        $waybill = $this->findOrFail($companyId, $id);
        $waybill->fill($data);

        if ($waybill->isDirty()) {
            $waybill->save();
        }

        return $waybill->refresh()->loadDefaultRelations();
    }

    public function delete(int $companyId, int $id): void
    {
        $this->findOrFail($companyId, $id)->delete();
    }

    public function trackingCodeExists(int $companyId, string $trackingCode): bool
    {
        return $this->waybill
            ->newSharedQueryForCompany($companyId)
            ->where('bijak_tracking_code', $trackingCode)
            ->exists();
    }

    public function referralNumberExists(
        int $companyId,
        string $serialNumber,
        string $referralNumber,
        ?int $ignoreWaybillId = null,
    ): bool {
        return $this->numberExists(
            $companyId,
            $serialNumber,
            'referral_number',
            $referralNumber,
            $ignoreWaybillId,
        );
    }

    public function bijakNumberExists(
        int $companyId,
        string $serialNumber,
        string $bijakNumber,
        ?int $ignoreWaybillId = null,
    ): bool {
        return $this->numberExists(
            $companyId,
            $serialNumber,
            'bijak_number',
            $bijakNumber,
            $ignoreWaybillId,
        );
    }

    private function numberExists(
        int $companyId,
        string $serialNumber,
        string $numberColumn,
        string $number,
        ?int $ignoreWaybillId,
    ): bool {
        return $this->query($companyId)
            ->where('serial_number', $serialNumber)
            ->where($numberColumn, $number)
            ->when($ignoreWaybillId !== null, fn ($query) => $query->whereKeyNot($ignoreWaybillId))
            ->exists();
    }
}
