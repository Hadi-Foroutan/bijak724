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
     * The companies that issued the most waybills (completed ones), whenever they were issued.
     *
     * @return list<array{company_id: int, name: string, organization_code: string|null, count: int}>
     */
    public function topCompanies(int $limit): array;

    /**
     * Issued (completed) waybills of every month from the first month to the last one, by issue date.
     *
     * @return array<string, int> `Y-m` => count (months without waybills are missing)
     */
    public function waybillsByMonth(CarbonInterface $from, CarbonInterface $to): array;

    /**
     * For every month, the companies that issued the most waybills in it.
     *
     * @return array<string, list<array{company_id: int, name: string, organization_code: string|null, count: int}>> `Y-m` => companies
     */
    public function topCompaniesByMonth(CarbonInterface $from, CarbonInterface $to, int $limit = 7): array;
}
