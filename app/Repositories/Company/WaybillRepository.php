<?php

namespace App\Repositories\Company;

use App\Enums\WaybillStatus;
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

    public function findOrFailForUpdate(int $companyId, int $id): Waybill
    {
        return $this->query($companyId)
            ->with($this->waybill->defaultRelations())
            ->lockForUpdate()
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

    /** @param list<string> $columns */
    public function hasIssuedReference(int $companyId, array $columns, int $referenceId): bool
    {
        return $this->issuedQuery($companyId)
            ->where(function (Builder $query) use ($columns, $referenceId): void {
                foreach ($columns as $column) {
                    $query->orWhere($column, $referenceId);
                }
            })
            ->exists();
    }

    public function hasIssuedCargoReference(int $companyId, string $column, int $referenceId): bool
    {
        return $this->issuedQuery($companyId)
            ->whereHas('cargos', fn (Builder $query): Builder => $query->where($column, $referenceId))
            ->exists();
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
        string $referralSerial,
        string $referralNumber,
        ?int $ignoreWaybillId = null,
    ): bool {
        return $this->waybill
            ->newSharedQueryForCompany($companyId)
            ->where('owner_company_id', $companyId)
            ->where('referral_serial', $referralSerial)
            ->where('referral_number', $referralNumber)
            ->when($ignoreWaybillId !== null, fn (Builder $query): Builder => $query->whereKeyNot($ignoreWaybillId))
            ->exists();
    }

    public function highestUsedReferralNumber(
        int $companyId,
        string $referralSerial,
        int $fromNumber,
        int $toNumber,
    ): ?int {
        $highestNumber = $this->waybill
            ->newSharedQueryForCompany($companyId)
            ->where('owner_company_id', $companyId)
            ->where('referral_serial', $referralSerial)
            ->whereNotNull('referral_number')
            ->whereRaw('CAST(referral_number AS UNSIGNED) BETWEEN ? AND ?', [$fromNumber, $toNumber])
            ->selectRaw('MAX(CAST(referral_number AS UNSIGNED)) AS highest_number')
            ->value('highest_number');

        return $highestNumber === null ? null : (int) $highestNumber;
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

    public function firstAvailableBijakNumber(
        int $companyId,
        string $serialNumber,
        int $fromNumber,
        int $toNumber,
    ): ?int {
        $firstAvailableNumber = $fromNumber;
        $usedNumbers = $this->waybill
            ->newSharedQueryForCompany($companyId)
            ->select('bijak_number')
            ->where('owner_company_id', $companyId)
            ->where('serial_number', $serialNumber)
            ->whereNotNull('bijak_number')
            ->whereIn('status', [
                WaybillStatus::Completed->value,
                WaybillStatus::Canceled->value,
            ])
            ->whereBetween('bijak_number', [$fromNumber, $toNumber])
            ->orderByRaw('CAST(bijak_number AS UNSIGNED)')
            ->cursor();

        foreach ($usedNumbers as $waybill) {
            $usedNumber = (int) $waybill->bijak_number;

            if ($usedNumber > $firstAvailableNumber) {
                break;
            }

            if ($usedNumber === $firstAvailableNumber) {
                $firstAvailableNumber++;
            }
        }

        return $firstAvailableNumber <= $toNumber ? $firstAvailableNumber : null;
    }

    private function numberExists(
        int $companyId,
        string $serialNumber,
        string $numberColumn,
        string $number,
        ?int $ignoreWaybillId,
    ): bool {
        return $this->waybill
            ->newSharedQueryForCompany($companyId)
            ->where('owner_company_id', $companyId)
            ->where('serial_number', $serialNumber)
            ->where($numberColumn, $number)
            ->when($ignoreWaybillId !== null, fn ($query) => $query->whereKeyNot($ignoreWaybillId))
            ->exists();
    }

    /** @return Builder<Waybill> */
    private function issuedQuery(int $companyId): Builder
    {
        return $this->query($companyId)->whereIn('status', [
            WaybillStatus::Completed->value,
            WaybillStatus::Canceled->value,
        ]);
    }
}
