<?php

namespace App\Services\Company\Dashboard;

use App\Helpers\ServiceResult;
use App\Interfaces\Company\DashboardRepositoryInterface;
use Carbon\CarbonImmutable;
use App\Support\DashboardCache;

class DashboardService
{
    public function __construct(
        protected DashboardRepositoryInterface $dashboardRepository,
        protected DashboardCache               $cache,
    )
    {
    }

    public function summary(int $companyId): ServiceResult
    {
        $today = CarbonImmutable::today();

        return ServiceResult::success(
            $this->cache->remember(
                ["company-{$companyId}", 'summary', $today->toDateString()],
                fn(): array => $this->dashboardRepository->summary($companyId, $today),
            ),
        );
    }

    public function dailyWaybills(int $companyId): ServiceResult
    {
        $to = CarbonImmutable::today()->endOfDay();
        $from = $to->startOfDay()->subDays(29);

        return ServiceResult::success(
            $this->cache->remember(
                ["company-{$companyId}", 'daily-waybills', $to->toDateString()],
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
                                    'count' => $counts->get($date->toDateString(), 0),
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
            $this->cache->remember(
                ["company-{$companyId}", 'monthly-waybills', $to->format('Y-m')],
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
                                    'count' => (int)$counts->get($monthKey, 0),
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
            $this->cache->remember(
                ["company-{$companyId}", 'top-cargos'],
                fn(): array => [
                    'items' => $this->dashboardRepository->topCargos($companyId),
                ],
            ),
        );
    }

    public function topDrivers(int $companyId): ServiceResult
    {
        return ServiceResult::success(
            $this->cache->remember(
                ["company-{$companyId}", 'top-drivers'],
                fn(): array => [
                    'items' => $this->dashboardRepository->topDrivers($companyId),
                ],
            ),
        );
    }
}
