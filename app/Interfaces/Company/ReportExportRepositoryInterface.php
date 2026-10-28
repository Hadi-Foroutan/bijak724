<?php

namespace App\Interfaces\Company;

use App\Models\ReportExport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/** Report exports (Excel/PDF files built on the queue) of a company: every user of the company sees all of them. */
interface ReportExportRepositoryInterface
{
    /** Newest first. @return Collection<int, ReportExport>|LengthAwarePaginator */
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator;

    public function findOrFail(int $companyId, int $id): ReportExport;

    /** For the queue worker and the download link: no owner scope, the owner (`user`) is loaded. */
    public function find(int $id): ?ReportExport;

    /** @param array<string, mixed> $data */
    public function create(array $data): ReportExport;

    /** @param array<string, mixed> $data */
    public function update(ReportExport $export, array $data): ReportExport;

    public function delete(ReportExport $export): void;

    /** Exports a user asked for that are still waiting in the queue or being built (the limit is per user). */
    public function activeCount(int $companyId, int $userId): int;

    /** Completed exports whose retention time is over. @return Collection<int, ReportExport> */
    public function expiredCompleted(): Collection;
}
