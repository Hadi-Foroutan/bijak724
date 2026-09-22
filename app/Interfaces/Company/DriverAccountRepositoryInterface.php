<?php

namespace App\Interfaces\Company;

use App\Models\Company\DriverAccount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface DriverAccountRepositoryInterface
{
    /** @return Builder<DriverAccount> */
    public function query(int $companyId): Builder;

    /** @return Collection<int, DriverAccount>|LengthAwarePaginator */
    public function searchForDriver(int $companyId, int $driverId, array $filters): Collection|LengthAwarePaginator;

    public function findForDriverOrFail(int $companyId, int $driverId, int $accountId): DriverAccount;

    /** @param array<string, mixed> $data */
    public function createForDriver(int $companyId, int $driverId, array $data): DriverAccount;

    /** @param array<string, mixed> $data */
    public function updateForDriver(
        int $companyId,
        int $driverId,
        int $accountId,
        array $data,
    ): DriverAccount;

    public function deleteForDriver(int $companyId, int $driverId, int $accountId): void;
}
