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
        protected DashboardCache                    $cache,
    )
    {
    }

    /** Totals of the whole system and today's numbers. */
    public function summary(): ServiceResult
    {
        $today = CarbonImmutable::today();

        return ServiceResult::success(
            $this->cache->remember(
                ['admin', 'summary', $today->toDateString()],
                fn(): array => $this->dashboardRepository->summary($today),
            ),
        );
    }

    /** The 7 companies that issued the most waybills, whenever they were issued. */
    public function topCompanies(): ServiceResult
    {
        return ServiceResult::success(
            $this->cache->remember(
                ['admin', 'top-companies'],
                fn(): array => ['items' => $this->dashboardRepository->topCompanies(7)],
            ),
        );
    }
}
