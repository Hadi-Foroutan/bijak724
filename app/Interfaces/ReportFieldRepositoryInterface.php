<?php

namespace App\Interfaces;

use App\Models\ReportField;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/** The waybill fields admins offer in the report builder. */
interface ReportFieldRepositoryInterface
{
    /** With the number of reports using each field. @return Collection<int, ReportField>|LengthAwarePaginator */
    public function search(array $filters): Collection|LengthAwarePaginator;

    /** @return Collection<int, ReportField> the fields users may pick, in the admin's order */
    public function active(): Collection;

    public function findOrFail(int $id): ReportField;

    /** @param array<string, mixed> $data */
    public function create(array $data): ReportField;

    /** @param array<string, mixed> $data */
    public function update(ReportField $field, array $data): ReportField;

    /** Also unlinks the field from every report that used it. */
    public function delete(ReportField $field): void;

    public function nextSortOrder(): int;
}
