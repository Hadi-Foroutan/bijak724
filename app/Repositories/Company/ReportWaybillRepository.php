<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\ReportWaybillRepositoryInterface;
use App\Models\Company\Waybill;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

class ReportWaybillRepository implements ReportWaybillRepositoryInterface
{
    public function __construct(protected WaybillReportFilterApplier $filterApplier)
    {
    }

    public function search(int $companyId, ?array $creatorIds, array $relations, array $filters): Collection|LengthAwarePaginator
    {
        $query = $this->query($companyId, $creatorIds, $relations, $filters);

        return $query->getModel()->advancedSearchResults($query, ['paginate' => true, ...$filters]);
    }

    public function count(int $companyId, ?array $creatorIds, array $filters): int
    {
        return $this->query($companyId, $creatorIds, [], $filters)->reorder()->count();
    }

    public function lazy(int $companyId, ?array $creatorIds, array $relations, array $filters, int $chunkSize): LazyCollection
    {
        return $this->query($companyId, $creatorIds, $relations, $filters)->lazy($chunkSize);
    }

    /**
     * @param list<int>|null $creatorIds
     * @param list<string> $relations
     * @param array<string, mixed> $filters
     * @return Builder<Waybill>
     */
    private function query(int $companyId, ?array $creatorIds, array $relations, array $filters): Builder
    {
        // A filter dialog sends every field, blank ones included; a blank `eq-status=` would match nothing.
        $filters = array_filter($filters, fn(mixed $value): bool => $value !== null && $value !== '' && $value !== []);

        $advanced = [
            ...Arr::except($filters, ['paginate', 'page', 'itemsPerPage']),
            ...$this->filterApplier->sortKeys($filters),
        ];

        $query = Waybill::queryForCompany($companyId)
            ->with($relations)
            ->advancedSearch($advanced);

        $this->filterApplier->apply($query, $filters);

        return $creatorIds === null ? $query : $query->whereIn('created_by', $creatorIds);
    }
}
