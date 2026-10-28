<?php

namespace App\Interfaces\Company;

use App\Models\ReportDefinition;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/** Saved reports of a company: every user of the company sees all of them. */
interface ReportRepositoryInterface
{
    /** @return Collection<int, ReportDefinition>|LengthAwarePaginator */
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator;

    /** The report with its linked fields (in the user's order). */
    public function findOrFail(int $companyId, int $id): ReportDefinition;

    /** `$userId` is only recorded as the creator. */
    public function create(int $companyId, int $userId, string $name): ReportDefinition;

    /** @param array<string, mixed> $data */
    public function update(ReportDefinition $report, array $data): ReportDefinition;

    public function delete(ReportDefinition $report): void;

    /**
     * @param list<int> $fieldIds in the order they should appear
     * @param array<int, array{align: string, width: int|null}> $options layout per field id (missing = right, fit the content)
     */
    public function syncFields(ReportDefinition $report, array $fieldIds, array $options = []): ReportDefinition;
}
