<?php

namespace App\Repositories;

use App\Enums\WaybillStatus;
use App\Interfaces\AdminDashboardRepositoryInterface;
use App\Models\Company;
use App\Models\User;
use App\Models\Waybill;
use App\Services\Company\CompanyTableRegistry;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminDashboardRepository implements AdminDashboardRepositoryInterface
{
    public function __construct(protected CompanyTableRegistry $companyTableRegistry)
    {
    }

    public function summary(CarbonInterface $today): array
    {
        $startOfDay = $today->copy()->startOfDay();
        $endOfDay = $today->copy()->endOfDay();

        // The shared `waybills` table has one row for every waybill of every company.
        return [
            'total_waybills' => Waybill::query()->count(),
            'today_waybills' => Waybill::query()->whereBetween('created_at', [$startOfDay, $endOfDay])->count(),
            // Companies that issued at least one waybill today.
            'active_companies_today' => Waybill::query()
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->distinct()
                ->count('company_id'),
            'total_companies' => Company::query()->count(),
            // The users of the companies (the admin panel's own staff have no company).
            'total_users' => User::query()->whereNotNull('company_id')->count(),
        ];
    }

    public function topCompanies(int $limit = 7): array
    {
        $counts = $this->issuedWaybillCounts();

        if ($counts === null) {
            return [];
        }

        $rows = DB::query()
            ->fromSub($counts, 'issued')
            ->join('companies', 'companies.id', '=', 'issued.company_id')
            ->selectRaw('companies.id as company_id, companies.name, companies.organization_code, SUM(issued.waybills_count) as waybills_count')
            ->groupBy('companies.id', 'companies.name', 'companies.organization_code')
            ->orderByDesc('waybills_count')
            ->orderBy('companies.id')
            ->limit($limit)
            ->get();

        return $rows->map(fn(object $row): array => [
            'company_id' => (int)$row->company_id,
            'name' => (string)$row->name,
            'organization_code' => $row->organization_code,
            'count' => (int)$row->waybills_count,
        ])->all();
    }

    public function waybillsByMonth(CarbonInterface $from, CarbonInterface $to): array
    {
        $counts = $this->issuedWaybillCounts($from, $to);

        if ($counts === null) {
            return [];
        }

        return DB::query()
            ->fromSub($counts, 'issued')
            ->selectRaw('issued.month, SUM(issued.waybills_count) as waybills_count')
            ->groupBy('issued.month')
            ->pluck('waybills_count', 'month')
            ->map(fn(mixed $count): int => (int)$count)
            ->all();
    }

    public function topCompaniesByMonth(
        CarbonInterface $from,
        CarbonInterface $to,
        ?int $limit = null
    ): array {
        $counts = $this->issuedWaybillCounts($from, $to);

        if ($counts === null) {
            return [];
        }

        return DB::query()
            ->fromSub($counts, 'issued')
            ->join('companies', 'companies.id', '=', 'issued.company_id')
            ->selectRaw('issued.month, companies.id as company_id, companies.name, companies.organization_code, SUM(issued.waybills_count) as waybills_count')
            ->groupBy('issued.month', 'companies.id', 'companies.name', 'companies.organization_code')
            ->get()
            ->groupBy('month')
            ->map(fn(Collection $rows): array => $rows
                ->sortBy([['waybills_count', 'desc'], ['company_id', 'asc']])
                ->when($limit !== null, fn(Collection $rows) => $rows->take($limit))
                ->map(fn(object $row): array => [
                    'company_id' => (int) $row->company_id,
                    'name' => (string) $row->name,
                    'organization_code' => $row->organization_code,
                    'count' => (int) $row->waybills_count,
                ])
                ->values()
                ->all())
            ->all();
    }

    /**
     * Issued (completed) waybills per company. Every company that owns its data has its own waybills table
     * (its branches write into it, told apart by `owner_company_id`), so the counts are read from each table and put together.
     * With a period the counts are also split by month (`month` = `Y-m` of the issue date) and only that period is read.
     */
    private function issuedWaybillCounts(?CarbonInterface $from = null, ?CarbonInterface $to = null): ?QueryBuilder
    {
        $perMonth = $from !== null && $to !== null;

        $counts = Company::query()
            ->whereNull('parent_id')
            ->pluck('id')
            ->map(function (int $companyId) use ($perMonth, $from, $to): ?QueryBuilder {
                $table = $this->companyTableRegistry->tableName($companyId, 'waybills');

                if (!Schema::hasTable($table)) {
                    return null;
                }

                return DB::table($table)
                    ->when(Schema::hasColumn($table, 'deleted_at'), fn(QueryBuilder $query) => $query->whereNull('deleted_at'))
                    ->where('status', WaybillStatus::Completed->value)
                    ->when($perMonth, fn(QueryBuilder $query) => $query
                        ->whereBetween('issued_at', [$from, $to])
                        ->selectRaw('SUBSTR(issued_at, 1, 7) as month')
                        ->groupBy('month'))
                    ->selectRaw('owner_company_id as company_id, COUNT(*) as waybills_count')
                    ->groupBy('owner_company_id');
            })
            ->filter()
            ->values();

        if ($counts->isEmpty()) {
            return null;
        }

        /** @var QueryBuilder $union */
        $union = $counts->shift();

        foreach ($counts as $query) {
            $union->unionAll($query);
        }

        return $union;
    }
}
