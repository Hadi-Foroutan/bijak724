<?php

namespace App\Services\Admin;

use App\Helpers\ServiceResult;
use App\Interfaces\AdminDashboardRepositoryInterface;
use App\Support\DashboardCache;
use Carbon\CarbonImmutable;

/** The admin dashboard: each part has its own method and its own cache entry (DASHBOARD_CACHE switches the cache). */
class DashboardService
{
    public function __construct(
        protected AdminDashboardRepositoryInterface $dashboardRepository,
        protected DashboardCache $cache,
    ) {}

    /** Totals of the whole system and today's numbers. */
    public function summary(): ServiceResult
    {
        $today = CarbonImmutable::today();

        return ServiceResult::success(
            $this->cache->remember(
                ['admin', 'summary', $today->toDateString()],
                fn (): array => $this->dashboardRepository->summary($today),
            ),
        );
    }

    /** The 7 companies that issued the most waybills, whenever they were issued. */
    public function topSevenCompanies(): ServiceResult
    {
        return ServiceResult::success(
            $this->cache->remember(
                ['admin', 'top-seven-companies'],
                fn (): array => ['items' => $this->dashboardRepository->topCompanies(7)],
            ),
        );
    }

    /** The 7 companies that issued the most waybills, whenever they were issued. */
    public function topCompanies(): ServiceResult
    {
        return ServiceResult::success(
            $this->cache->remember(
                ['admin', 'top-companies'],
                fn (): array => ['items' => $this->dashboardRepository->topCompanies()],
            ),
        );
    }

    /** The issued waybills of each of the last 12 months (this month included). */
    public function monthlyWaybills(): ServiceResult
    {
        [$from, $to] = $this->lastTwelveMonths();

        return ServiceResult::success(
            $this->cache->remember(
                ['admin', 'monthly-waybills', $to->format('Y-m')],
                function () use ($from, $to): array {
                    $counts = $this->dashboardRepository->waybillsByMonth($from, $to);

                    return [
                        'from' => $from->format('Y-m'),
                        'to' => $to->format('Y-m'),
                        'items' => $this->months($from, fn (CarbonImmutable $month, string $key): array => [
                            'count' => (int) ($counts[$key] ?? 0),
                        ]),
                    ];
                },
            ),
        );
    }

    /** The 7 companies that issued the most waybills in the current month. */
    public function currentMonthTopCompanies(): ServiceResult
    {
        $from = CarbonImmutable::today()->startOfMonth();
        $to = $from->endOfMonth();
        $month = $from->format('Y-m');

        return ServiceResult::success(
            $this->cache->remember(
                ['admin', 'current-month-top-companies', $month],
                function () use ($from, $to, $month): array {
                    $companies = $this->dashboardRepository->topCompaniesByMonth($from, $to, 7);

                    return [
                        'items' => $companies[$month] ?? [],
                    ];
                },
            ),
        );
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} first day of the first month, last moment of this month */
    private function lastTwelveMonths(): array
    {
        $to = CarbonImmutable::today()->endOfMonth();

        return [$to->startOfMonth()->subMonths(11), $to];
    }

    /**
     * One entry per month, oldest first: `month` (`Y-m`), `label` and whatever the callback adds.
     *
     * @param  callable(CarbonImmutable, string): array<string, mixed>  $details
     * @return list<array<string, mixed>>
     */
    private function months(CarbonImmutable $from, callable $details): array
    {
        return collect(range(0, 11))
            ->map(function (int $offset) use ($from, $details): array {
                $month = $from->addMonths($offset);
                $key = $month->format('Y-m');

                return ['month' => $key, 'label' => $month->locale('fa')->translatedFormat('F Y'), ...$details($month, $key)];
            })
            ->all();
    }
}
