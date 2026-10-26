<?php

namespace App\Services\Company\Report;

use App\Models\Company\Waybill;
use App\Models\User;
use App\Services\Company\Waybill\WaybillVisibilityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

/** The company's waybills for a report: the dialog filters (WaybillReportFilters), advanced search filters, the user's visibility, and only the relations the chosen fields need. */
class WaybillReportQuery
{
    public function __construct(
        protected WaybillReportFieldCatalog $catalog,
        protected WaybillVisibilityService  $visibility,
        protected WaybillReportFilters      $filters,
    )
    {
    }

    /**
     * @param list<string> $fields
     * @param array<string, mixed> $filters
     * @return Builder<Waybill>
     */
    public function build(int $companyId, User $user, array $fields, array $filters): Builder
    {
        $advanced = [...Arr::except($filters, ['paginate', 'page', 'itemsPerPage']), ...$this->filters->sortKeys($filters)];

        $query = (new Waybill)->newQueryForCompany($companyId)
            ->with($this->catalog->relationsFor($fields))
            ->advancedSearch($advanced);

        $this->filters->apply($query, $filters);

        return $this->visibility->scope($query, $user);
    }
}
