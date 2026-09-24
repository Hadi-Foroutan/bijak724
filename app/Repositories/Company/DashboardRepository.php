<?php

namespace App\Repositories\Company;

use App\Enums\WaybillStatus;
use App\Interfaces\Company\DashboardRepositoryInterface;
use App\Interfaces\Company\DriverRepositoryInterface;
use App\Interfaces\Company\FleetRepositoryInterface;
use App\Interfaces\Company\WaybillCargoRepositoryInterface;
use App\Interfaces\Company\WaybillRepositoryInterface;
use App\Models\Cargo;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardRepository implements DashboardRepositoryInterface
{
    public function __construct(
        protected WaybillRepositoryInterface $waybillRepository,
        protected WaybillCargoRepositoryInterface $waybillCargoRepository,
        protected DriverRepositoryInterface $driverRepository,
        protected FleetRepositoryInterface $fleetRepository,
    ) {}

    public function summary(int $companyId, CarbonInterface $today): array
    {
        return [
            'total_waybills' => $this->waybillRepository->query($companyId)->count(),
            'today_waybills' => $this->waybillRepository
                ->query($companyId)
                ->whereBetween('created_at', [
                    $today->copy()->startOfDay(),
                    $today->copy()->endOfDay(),
                ])
                ->count(),
            'total_drivers' => $this->driverRepository->query($companyId)->count(),
            'total_fleets' => $this->fleetRepository->query($companyId)->count(),
        ];
    }

    public function issuedWaybillsByDay(
        int $companyId,
        CarbonInterface $from,
        CarbonInterface $to,
    ): Collection {
        $query = $this->waybillRepository->query($companyId);
        $issuedAt = $query->getModel()->qualifyColumn('issued_at');
        $periodExpression = "DATE({$issuedAt})";

        return $this->aggregateIssuedWaybills(
            $query,
            $from,
            $to,
            $periodExpression,
        );
    }

    public function issuedWaybillsByMonth(
        int $companyId,
        CarbonInterface $from,
        CarbonInterface $to,
    ): Collection {
        $query = $this->waybillRepository->query($companyId);
        $issuedAt = $query->getModel()->qualifyColumn('issued_at');

        return $this->aggregateIssuedWaybills(
            $query,
            $from,
            $to,
            $this->monthExpression($query, $issuedAt),
        );
    }

    public function topCargos(int $companyId, int $limit = 5): array
    {
        $query = $this->waybillCargoRepository->query($companyId);
        $waybillQuery = $this->waybillRepository->query($companyId);
        $cargoTable = $query->getModel()->getTable();
        $waybillModel = $waybillQuery->getModel();
        $waybillTable = $waybillModel->getTable();
        $masterCargoTable = (new Cargo)->getTable();

        $query
            ->join(
                $waybillTable,
                "{$waybillTable}.id",
                '=',
                "{$cargoTable}.waybill_id",
            )
            ->join(
                $masterCargoTable,
                "{$masterCargoTable}.id",
                '=',
                "{$cargoTable}.cargo_id",
            )
            ->where("{$waybillTable}.status", WaybillStatus::Completed->value)
            ->whereNotNull("{$waybillTable}.issued_at")
            ->whereNotNull("{$cargoTable}.cargo_id")
            ->when(
                $waybillModel->companyId() !== $companyId,
                fn (Builder $query): Builder => $query->where(
                    "{$waybillTable}.owner_company_id",
                    $companyId,
                ),
            )
            ->select([
                "{$cargoTable}.cargo_id",
                "{$masterCargoTable}.code as cargo_code",
                "{$masterCargoTable}.name as cargo_name",
            ])
            ->selectRaw("COUNT(DISTINCT {$cargoTable}.waybill_id) as waybill_count")
            ->groupBy([
                "{$cargoTable}.cargo_id",
                "{$masterCargoTable}.code",
                "{$masterCargoTable}.name",
            ])
            ->orderByDesc('waybill_count')
            ->orderBy("{$cargoTable}.cargo_id")
            ->limit($limit);

        return $query->get()
            ->map(fn ($cargo): array => [
                'cargo_id' => (int) $cargo->cargo_id,
                'cargo_code' => (int) $cargo->cargo_code,
                'cargo_name' => (string) $cargo->cargo_name,
                'count' => (int) $cargo->waybill_count,
            ])
            ->all();
    }

    public function topDrivers(int $companyId, int $limit = 10): array
    {
        $query = $this->waybillRepository->query($companyId);
        $driverQuery = $this->driverRepository->query($companyId);
        $waybillTable = $query->getModel()->getTable();
        $driverModel = $driverQuery->getModel();
        $driverTable = $driverModel->getTable();

        $query
            ->join(
                $driverTable,
                "{$driverTable}.id",
                '=',
                "{$waybillTable}.driver1_id",
            )
            ->where("{$waybillTable}.status", WaybillStatus::Completed->value)
            ->whereNotNull("{$waybillTable}.issued_at")
            ->whereNotNull("{$waybillTable}.driver1_id")
            ->when(
                $driverModel->companyId() !== $companyId,
                fn (Builder $query): Builder => $query->where(
                    "{$driverTable}.owner_company_id",
                    $companyId,
                ),
            )
            ->select([
                "{$waybillTable}.driver1_id as driver_id",
                "{$driverTable}.full_name",
                "{$driverTable}.national_code",
            ])
            ->selectRaw("COUNT({$waybillTable}.id) as waybill_count")
            ->groupBy([
                "{$waybillTable}.driver1_id",
                "{$driverTable}.full_name",
                "{$driverTable}.national_code",
            ])
            ->orderByDesc('waybill_count')
            ->orderBy("{$waybillTable}.driver1_id")
            ->limit($limit);

        return $query->get()
            ->map(fn ($driver): array => [
                'driver_id' => (int) $driver->driver_id,
                'full_name' => (string) $driver->full_name,
                'national_code' => (string) $driver->national_code,
                'count' => (int) $driver->waybill_count,
            ])
            ->all();
    }

    /** @return Collection<string, int> */
    private function aggregateIssuedWaybills(
        Builder $query,
        CarbonInterface $from,
        CarbonInterface $to,
        string $periodExpression,
    ): Collection {
        return $query
            ->where('status', WaybillStatus::Completed->value)
            ->whereNotNull('issued_at')
            ->whereBetween('issued_at', [$from, $to])
            ->selectRaw("{$periodExpression} as period, COUNT(*) as aggregate")
            ->groupByRaw($periodExpression)
            ->orderBy('period')
            ->pluck('aggregate', 'period')
            ->mapWithKeys(fn (mixed $count, mixed $period): array => [
                (string) $period => (int) $count,
            ]);
    }

    private function monthExpression(Builder $query, string $column): string
    {
        return match ($query->getConnection()->getDriverName()) {
            'mysql', 'mariadb' => "DATE_FORMAT({$column}, '%Y-%m')",
            'pgsql' => "TO_CHAR({$column}, 'YYYY-MM')",
            'sqlsrv' => "FORMAT({$column}, 'yyyy-MM')",
            default => "strftime('%Y-%m', {$column})",
        };
    }
}
