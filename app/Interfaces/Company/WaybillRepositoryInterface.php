<?php

namespace App\Interfaces\Company;

use App\Models\Company\Waybill;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\LazyCollection;

interface WaybillRepositoryInterface
{
    /** @return Builder<Waybill> */
    public function query(int $companyId): Builder;

    /** @return Collection<int, Waybill>|LengthAwarePaginator */
    public function search(int $companyId, array $filters, ?User $viewer = null): Collection|LengthAwarePaginator;

    /** @param array<string, mixed> $data */
    public function create(int $companyId, array $data): Waybill;

    public function findOrFail(int $companyId, int $id, ?User $viewer = null): Waybill;

    public function findOrFailForUpdate(int $companyId, int $id): Waybill;

    /** @param array<string, mixed> $data */
    public function update(int $companyId, int $id, array $data): Waybill;

    /** Soft delete. */
    public function delete(int $companyId, int $id): void;

    public function restore(int $companyId, int $id): Waybill;

    /**
     * The issued waybills from one city to another in a period, with only their driver columns.
     *
     * @return LazyCollection<int, Waybill>
     */
    public function issuedOnRoute(
        int             $companyId,
        int             $originCityCode,
        int             $destinationCityCode,
        CarbonInterface $from,
        CarbonInterface $to,
        User            $viewer,
    ): LazyCollection;

    /** Whether the user issued (completed or canceled) any waybill in the company's waybill table. */
    public function hasIssuedBy(int $companyId, int $userId): bool;

    /** @param list<string> $columns */
    public function hasIssuedReference(int $companyId, array $columns, int $referenceId): bool;

    public function hasIssuedCargoReference(int $companyId, string $column, int $referenceId): bool;

    public function trackingCodeExists(int $companyId, string $trackingCode): bool;

    public function referralNumberExists(
        int    $companyId,
        string $referralSerial,
        string $referralNumber,
        ?int   $ignoreWaybillId = null,
    ): bool;

    public function highestUsedReferralNumber(
        int    $companyId,
        string $referralSerial,
        int    $fromNumber,
        int    $toNumber,
    ): ?int;

    public function bijakNumberExists(
        int    $companyId,
        string $serialNumber,
        string $bijakNumber,
        ?int   $ignoreWaybillId = null,
    ): bool;

    public function firstAvailableBijakNumber(
        int    $companyId,
        string $serialNumber,
        int    $fromNumber,
        int    $toNumber,
    ): ?int;
}
