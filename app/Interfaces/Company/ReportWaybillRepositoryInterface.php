<?php

namespace App\Interfaces\Company;

use App\Models\Company\Waybill;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

/**
 * The company's waybills as a report sees them: the dialog filters, the advanced search filters and the
 * viewer's visibility permissions, with only the given relations loaded.
 */
interface ReportWaybillRepositoryInterface
{
    /**
     * Paginated unless `paginate` is explicitly turned off.
     *
     * @param list<int>|null $creatorIds creators whose waybills may be seen; null = everyone's
     * @param list<string> $relations
     * @param array<string, mixed> $filters
     * @return Collection<int, Waybill>|LengthAwarePaginator
     */
    public function search(int $companyId, ?array $creatorIds, array $relations, array $filters): Collection|LengthAwarePaginator;

    /**
     * @param list<int>|null $creatorIds
     * @param array<string, mixed> $filters
     */
    public function count(int $companyId, ?array $creatorIds, array $filters): int;

    /**
     * Every matching waybill, read in chunks so an export never holds them all in memory.
     *
     * @param list<int>|null $creatorIds
     * @param list<string> $relations
     * @param array<string, mixed> $filters
     * @return LazyCollection<int, Waybill>
     */
    public function lazy(int $companyId, ?array $creatorIds, array $relations, array $filters, int $chunkSize): LazyCollection;
}
