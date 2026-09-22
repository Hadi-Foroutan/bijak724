<?php

namespace App\Interfaces\Company;

use App\Models\Company\Waybill;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface WaybillRepositoryInterface
{
    /** @return Builder<Waybill> */
    public function query(int $companyId): Builder;

    /** @return Collection<int, Waybill>|LengthAwarePaginator */
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator;

    /** @param array<string, mixed> $data */
    public function create(int $companyId, array $data): Waybill;

    public function findOrFail(int $companyId, int $id): Waybill;

    /** @param array<string, mixed> $data */
    public function update(int $companyId, int $id, array $data): Waybill;

    public function delete(int $companyId, int $id): void;

    public function trackingCodeExists(int $companyId, string $trackingCode): bool;

    public function referralNumberExists(
        int $companyId,
        string $serialNumber,
        string $referralNumber,
        ?int $ignoreWaybillId = null,
    ): bool;

    public function bijakNumberExists(
        int $companyId,
        string $serialNumber,
        string $bijakNumber,
        ?int $ignoreWaybillId = null,
    ): bool;
}
