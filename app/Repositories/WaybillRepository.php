<?php

namespace App\Repositories;

use App\Enums\WaybillStatus;
use App\Interfaces\Company\WaybillRepositoryInterface as CompanyWaybillRepositoryInterface;
use App\Interfaces\WaybillRepositoryInterface;
use App\Models\Waybill;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class WaybillRepository implements WaybillRepositoryInterface
{
    public function __construct(
        protected CompanyWaybillRepositoryInterface $companyWaybillRepository,
    ) {}

    public function search(array $filters): Collection|LengthAwarePaginator
    {
        [$sharedFilters, $dynamicFilters] = $this->prepareFilters($filters);

        $records = Waybill::searchRecords(
            $sharedFilters,
            function (Builder $query) use ($dynamicFilters): Builder {
                $query->with('company');
                $this->applyDynamicFilters($query, $dynamicFilters);

                return $query;
            },
        );

        $this->loadCompanyWaybills($records);

        return $records;
    }

    public function create(int $companyId, int $waybillId): Waybill
    {
        return Waybill::query()->firstOrCreate([
            'company_id' => $companyId,
            'waybill_id' => $waybillId,
        ]);
    }

    public function delete(int $companyId, int $waybillId): void
    {
        Waybill::query()
            ->where('company_id', $companyId)
            ->where('waybill_id', $waybillId)
            ->delete();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function prepareFilters(array $filters): array
    {
        $sharedFilters = $filters;
        $dynamicFilters = [];

        foreach (['serial_number', 'referral_number', 'bijak_tracking_code'] as $field) {
            if (array_key_exists($field, $filters)) {
                $dynamicFilters[$field] = $filters[$field];
            }

            unset($sharedFilters[$field]);
        }

        if (isset($filters['status'])) {
            $status = WaybillStatus::tryFrom((string) $filters['status']);
            $dynamicFilters['eq-status'] = $status?->value ?? '__invalid_status__';
        }

        unset($sharedFilters['status']);

        if (isset($filters['company_id'])) {
            $sharedFilters['eq-company_id'] = $filters['company_id'];
        }

        unset($sharedFilters['company_id']);

        if (isset($filters['company_name'])) {
            $sharedFilters['company__name'] = $filters['company_name'];
        }

        unset($sharedFilters['company_name']);

        if (isset($filters['created_at_from'])) {
            $sharedFilters['min-created_at'] = $filters['created_at_from'].' 00:00:00';
        }

        if (isset($filters['created_at_to'])) {
            $sharedFilters['max-created_at'] = $filters['created_at_to'].' 23:59:59';
        }

        unset($sharedFilters['created_at_from'], $sharedFilters['created_at_to']);

        return [$sharedFilters, $dynamicFilters];
    }

    /** @param array<string, mixed> $dynamicFilters */
    private function applyDynamicFilters(Builder $query, array $dynamicFilters): void
    {
        if ($dynamicFilters === []) {
            return;
        }

        $companyIds = (clone $query)
            ->reorder()
            ->select('company_id')
            ->distinct()
            ->pluck('company_id');

        if ($companyIds->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $query) use ($companyIds, $dynamicFilters): void {
            foreach ($companyIds as $companyId) {
                $waybillIds = $this->companyWaybillRepository
                    ->query((int) $companyId)
                    ->advancedSearch($dynamicFilters)
                    ->reorder()
                    ->select('id');

                $query->orWhere(function (Builder $query) use ($companyId, $waybillIds): void {
                    $query->where('company_id', $companyId)
                        ->whereIn('waybill_id', $waybillIds);
                });
            }
        });
    }

    /** @param Collection<int, Waybill>|LengthAwarePaginator $records */
    private function loadCompanyWaybills(Collection|LengthAwarePaginator $records): void
    {
        $collection = $records instanceof LengthAwarePaginator
            ? $records->getCollection()
            : $records;

        $collection
            ->groupBy('company_id')
            ->each(function (Collection $indexes, int|string $companyId): void {
                $companyWaybills = $this->companyWaybillRepository
                    ->query((int) $companyId)
                    ->with('creator')
                    ->whereKey($indexes->pluck('waybill_id'))
                    ->get()
                    ->keyBy('id');

                $indexes->each(function (Waybill $index) use ($companyWaybills): void {
                    $index->setRelation('companyWaybill', $companyWaybills->get($index->waybill_id));
                });
            });
    }
}
