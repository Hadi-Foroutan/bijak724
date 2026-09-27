<?php

namespace App\Repositories\Company;

use App\Enums\CompanyParentEnum;
use App\Enums\UserStatusEnum;
use App\Interfaces\CompanyInterface;
use App\Models\Company;
use App\Services\Company\CompanyTableRegistry;
use App\Services\TreeBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompanyRepository implements CompanyInterface
{
    public function __construct(
        protected TreeBuilder $treeBuilder,
        protected CompanyTableRegistry $companyTableRegistry,
    ) {}

    public function all(array $params)
    {
        return Company::searchRecords(
            $params,
            fn ($query) => $query->when(
                $this->onlyParentOptions($params),
                fn ($query) => $query
                    ->where('parent_type', CompanyParentEnum::ORIGINAL->value)
                    ->whereNull('parent_id'),
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{active_companies_count: int, total_waybills_count: int, companies: Collection<int, Company>|LengthAwarePaginator}
     */
    public function aggregateReport(array $params): array
    {
        $waybillStatistics = $this->waybillStatisticsQuery();
        $totalWaybillsCount = (int) DB::query()
            ->fromSub(clone $waybillStatistics, 'waybill_totals')
            ->sum('waybills_count');
        $companyFilters = $this->prepareAggregateReportFilters($params);
        $aggregateOrderField = in_array(
            $params['order_field'] ?? null,
            ['waybills_count', 'last_waybill_at'],
            true,
        ) ? $params['order_field'] : null;

        if ($aggregateOrderField !== null) {
            unset($companyFilters['order_field'], $companyFilters['order_type']);
        }

        $company = new Company;
        $query = Company::query()
            ->leftJoinSub($waybillStatistics, 'waybill_statistics', function (JoinClause $join): void {
                $join->on('companies.id', '=', 'waybill_statistics.company_id');
            })
            ->select('companies.*')
            ->selectRaw('COALESCE(waybill_statistics.waybills_count, 0) as waybills_count')
            ->addSelect('waybill_statistics.last_waybill_at')
            ->advancedSearch($companyFilters);

        $this->applyAggregateReportFilters($query, $params);

        if ($aggregateOrderField !== null) {
            $query->reorder()->orderBy(
                $aggregateOrderField,
                strtolower((string) ($params['order_type'] ?? 'desc')),
            );
        }

        return [
            'active_companies_count' => Company::query()
                ->where('status', UserStatusEnum::ACTIVE->value)
                ->count(),
            'total_waybills_count' => $totalWaybillsCount,
            'companies' => $company->advancedSearchResults($query, $params),
        ];
    }

    public function tree(array $params): Collection
    {
        unset($params['tree'], $params['paginate']);

        /** @var Collection<int, Company> $companies */
        $companies = Company::searchRecords(
            $params,
            fn ($query) => $query->when(
                $this->onlyParentOptions($params),
                fn ($query) => $query
                    ->where('parent_type', CompanyParentEnum::ORIGINAL->value)
                    ->whereNull('parent_id'),
            ),
        );

        return $this->treeBuilder->build($companies);
    }

    public function create(array $data): Company
    {
        return Company::query()->create($data);
    }

    public function update(Company $company, array $data): Company
    {
        $company->update($data);

        return $company->refresh()->load('account');
    }

    public function delete(Company $company): void
    {
        $company->delete();
    }

    public function findByOrganizationCode(string $code): ?Company
    {
        return Company::query()->where('organization_code', $code)->first();
    }

    public function findByNationalCode(string $code): ?Company
    {
        return Company::query()->where('national_code', $code)->first();
    }

    /** @param array<string, mixed> $params */
    private function onlyParentOptions(array $params): bool
    {
        return filter_var(
            $params['parent_options'] ?? $params['is_parent'] ?? false,
            FILTER_VALIDATE_BOOL,
        );
    }

    /** @param array<string, mixed> $params */
    private function prepareAggregateReportFilters(array $params): array
    {
        $filters = $params;

        if (array_key_exists('company_id', $filters)) {
            $filters['eq-id'] = $filters['company_id'];
        }

        if (array_key_exists('status', $filters)) {
            $filters['eq-status'] = $filters['status'];
        }

        if (array_key_exists('parent_type', $filters)) {
            $filters['eq-parent_type'] = $filters['parent_type'];
        }

        unset(
            $filters['company_id'],
            $filters['status'],
            $filters['parent_type'],
            $filters['min-waybills_count'],
            $filters['max-waybills_count'],
            $filters['eq-waybills_count'],
            $filters['last_waybill_from'],
            $filters['last_waybill_to'],
        );

        return $filters;
    }

    /** @param array<string, mixed> $params */
    private function applyAggregateReportFilters(Builder $query, array $params): void
    {
        $query
            ->when(
                array_key_exists('min-waybills_count', $params),
                fn (Builder $query): Builder => $query->whereRaw(
                    'COALESCE(waybill_statistics.waybills_count, 0) >= ?',
                    [(int) $params['min-waybills_count']],
                ),
            )
            ->when(
                array_key_exists('max-waybills_count', $params),
                fn (Builder $query): Builder => $query->whereRaw(
                    'COALESCE(waybill_statistics.waybills_count, 0) <= ?',
                    [(int) $params['max-waybills_count']],
                ),
            )
            ->when(
                array_key_exists('eq-waybills_count', $params),
                fn (Builder $query): Builder => $query->whereRaw(
                    'COALESCE(waybill_statistics.waybills_count, 0) = ?',
                    [(int) $params['eq-waybills_count']],
                ),
            )
            ->when(
                isset($params['last_waybill_from']),
                fn (Builder $query): Builder => $query->where(
                    'waybill_statistics.last_waybill_at',
                    '>=',
                    $params['last_waybill_from'].' 00:00:00',
                ),
            )
            ->when(
                isset($params['last_waybill_to']),
                fn (Builder $query): Builder => $query->where(
                    'waybill_statistics.last_waybill_at',
                    '<=',
                    $params['last_waybill_to'].' 23:59:59',
                ),
            );
    }

    private function waybillStatisticsQuery(): QueryBuilder
    {
        $statisticsQueries = Company::query()
            ->whereNull('parent_id')
            ->pluck('id')
            ->map(function (int $companyId): ?QueryBuilder {
                $tableName = $this->companyTableRegistry->tableName($companyId, 'waybills');

                if (! Schema::hasTable($tableName)) {
                    return null;
                }

                return DB::table($tableName)
                    ->selectRaw('owner_company_id as company_id, COUNT(*) as waybills_count, MAX(created_at) as last_waybill_at')
                    ->whereIn('owner_company_id', Company::query()->select('id'))
                    ->groupBy('owner_company_id');
            })
            ->filter()
            ->values();

        if ($statisticsQueries->isEmpty()) {
            return DB::query()
                ->selectRaw('NULL as company_id, 0 as waybills_count, NULL as last_waybill_at')
                ->whereRaw('1 = 0');
        }

        /** @var QueryBuilder $unionQuery */
        $unionQuery = clone $statisticsQueries->shift();

        foreach ($statisticsQueries as $statisticsQuery) {
            $unionQuery->unionAll($statisticsQuery);
        }

        return DB::query()
            ->fromSub($unionQuery, 'company_waybill_statistics')
            ->selectRaw('company_id, SUM(waybills_count) as waybills_count, MAX(last_waybill_at) as last_waybill_at')
            ->groupBy('company_id');
    }
}
