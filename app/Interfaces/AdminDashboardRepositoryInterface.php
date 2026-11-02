<?php

namespace App\Interfaces;

use Carbon\CarbonInterface;

/** The numbers of the admin dashboard, across all companies. */
interface AdminDashboardRepositoryInterface
{
    /**
     * @return array{
     *     total_waybills: int,
     *     today_waybills: int,
     *     active_companies_today: int,
     *     total_companies: int,
     *     total_users: int
     * }
     */
    public function summary(CarbonInterface $today): array;

    /**
     * The companies that issued the most waybills (completed, by issue date) in a period.
     *
     * @return list<array{company_id: int, name: string, organization_code: string|null, count: int}>
     */
    public function topCompanies(CarbonInterface $from, CarbonInterface $to, int $limit = 7): array;
}
