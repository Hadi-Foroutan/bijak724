<?php

namespace App\Services\Company\Dashboard;

use App\Helpers\ServiceResult;
use App\Interfaces\Company\DashboardRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    private const CACHE_VERSION = 'v1';

    public function __construct(
        protected DashboardRepositoryInterface $dashboardRepository,
    ) {}

    public function summary(int $companyId): ServiceResult
    {
        $today = CarbonImmutable::today();

        return ServiceResult::success(
            Cache::remember(
                $this->cacheKey($companyId, 'summary', $today->toDateString()),
                $this->cacheTtl(),
                fn (): array => $this->dashboardRepository->summary($companyId, $today),
            ),
        );
    }

    public function dailyWaybills(int $companyId): ServiceResult
    {
        $to = CarbonImmutable::today()->endOfDay();
        $from = $to->startOfDay()->subDays(29);

        return ServiceResult::success(
            Cache::remember(
                $this->cacheKey($companyId, 'daily-waybills', $to->toDateString()),
                $this->cacheTtl(),
                function () use ($companyId, $from, $to): array {
                    $counts = $this->dashboardRepository->issuedWaybillsByDay(
                        $companyId,
                        $from,
                        $to,
                    );

                    return [
                        'from' => $from->toDateString(),
                        'to' => $to->toDateString(),
                        'items' => collect(range(0, 29))
                            ->map(function (int $dayOffset) use ($from, $counts): array {
                                $date = $from->addDays($dayOffset);

                                return [
                                    'date' => $date->toDateString(),
                                    'label' => $date->locale('fa')->translatedFormat('j F'),
                                    'count' => (int) $counts->get($date->toDateString(), 0),
                                ];
                            })
                            ->all(),
                    ];
                },
            ),
        );
    }

    public function monthlyWaybills(int $companyId): ServiceResult
    {
        $to = CarbonImmutable::today()->endOfMonth();
        $from = $to->startOfMonth()->subMonths(5);

        return ServiceResult::success(
            Cache::remember(
                $this->cacheKey($companyId, 'monthly-waybills', $to->format('Y-m')),
                $this->cacheTtl(),
                function () use ($companyId, $from, $to): array {
                    $counts = $this->dashboardRepository->issuedWaybillsByMonth(
                        $companyId,
                        $from,
                        $to,
                    );

                    return [
                        'from' => $from->format('Y-m'),
                        'to' => $to->format('Y-m'),
                        'items' => collect(range(0, 5))
                            ->map(function (int $monthOffset) use ($from, $counts): array {
                                $month = $from->addMonths($monthOffset);
                                $monthKey = $month->format('Y-m');

                                return [
                                    'month' => $monthKey,
                                    'label' => $month->locale('fa')->translatedFormat('F Y'),
                                    'count' => (int) $counts->get($monthKey, 0),
                                ];
                            })
                            ->all(),
                    ];
                },
            ),
        );
    }

    public function topCargos(int $companyId): ServiceResult
    {
        return ServiceResult::success(
            Cache::remember(
                $this->cacheKey($companyId, 'top-cargos'),
                $this->cacheTtl(),
                fn (): array => [
                    'items' => $this->dashboardRepository->topCargos($companyId),
                ],
            ),
        );
    }

    public function topDrivers(int $companyId): ServiceResult
    {
        return ServiceResult::success(
            Cache::remember(
                $this->cacheKey($companyId, 'top-drivers'),
                $this->cacheTtl(),
                fn (): array => [
                    'items' => $this->dashboardRepository->topDrivers($companyId),
                ],
            ),
        );
    }

    private function cacheKey(int $companyId, string $metric, ?string $period = null): string
    {
        return implode(':', array_filter([
            'dashboard',
            self::CACHE_VERSION,
            "company-{$companyId}",
            $metric,
            $period,
        ]));
    }

    private function cacheTtl(): int
    {
        return max(1, (int) config('dashboard.cache_ttl_seconds', 7200));
    }
}
