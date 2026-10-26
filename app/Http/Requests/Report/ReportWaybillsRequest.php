<?php

namespace App\Http\Requests\Report;

use App\Http\Requests\BaseRequest;
use App\Services\Company\Report\WaybillReportFilters;

/** Query string of GET reports/{report}/waybills: the filter dialog keys (other advanced-search keys pass through untouched). */
class ReportWaybillsRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return WaybillReportFilters::rules();
    }
}
