<?php

namespace App\Interfaces\Company;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface DashboardRepositoryInterface
{
    /**
     * @return array{
     *     total_waybills: int,
     *     today_waybills: int,
     *     total_drivers: int,
     *     total_fleets: int
     * }
     */
    public function summary(int $companyId, CarbonInterface $today): array;

    /** @return Collection<string, int> */
    public function issuedWaybillsByDay(
        int $companyId,
        CarbonInterface $from,
        CarbonInterface $to,
    ): Collection;

    /** @return Collection<string, int> */
    public function issuedWaybillsByMonth(
        int $companyId,
        CarbonInterface $from,
        CarbonInterface $to,
    ): Collection;

    /**
     * @return list<array{
     *     cargo_id: int,
     *     cargo_code: int,
     *     cargo_name: string,
     *     count: int
     * }>
     */
    public function topCargos(int $companyId, int $limit = 5): array;

    /**
     * @return list<array{
     *     driver_id: int,
     *     full_name: string,
     *     national_code: string,
     *     count: int
     * }>
     */
    public function topDrivers(int $companyId, int $limit = 10): array;
}
