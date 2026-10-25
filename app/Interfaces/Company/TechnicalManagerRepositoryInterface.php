<?php

namespace App\Interfaces\Company;

use App\Models\TechnicalManager;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TechnicalManagerRepositoryInterface
{
    /** @return Collection<int, TechnicalManager>|LengthAwarePaginator */
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator;

    public function findOrFail(int $companyId, int $id): TechnicalManager;

    /** Loads the row with a write lock; call it inside a transaction. */
    public function findOrFailForUpdate(int $companyId, int $id): TechnicalManager;

    /** @param array<string, mixed> $data */
    public function create(int $companyId, array $data): TechnicalManager;

    /** @param array<string, mixed> $data */
    public function update(TechnicalManager $technicalManager, array $data): TechnicalManager;

    public function delete(TechnicalManager $technicalManager): void;

    /**
     * Number of waybills issued with each technical manager on the given day.
     *
     * @param list<int> $technicalManagerIds
     * @return array<int, int> keyed by technical manager id
     */
    public function issuedCountsForDay(int $companyId, array $technicalManagerIds, CarbonInterface $day): array;
}
