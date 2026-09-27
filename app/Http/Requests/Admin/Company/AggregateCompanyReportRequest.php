<?php

namespace App\Http\Requests\Admin\Company;

use App\Enums\CompanyParentEnum;
use App\Enums\UserStatusEnum;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class AggregateCompanyReportRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable', 'integer', Rule::exists('companies', 'id')],
            'eq-id' => ['nullable', 'integer', Rule::exists('companies', 'id')],
            'min-id' => ['nullable', 'integer', 'min:1'],
            'max-id' => ['nullable', 'integer', 'min:1'],
            'parent_id' => ['nullable', 'integer', Rule::exists('companies', 'id')],
            'eq-parent_id' => ['nullable', 'integer', Rule::exists('companies', 'id')],
            'name' => ['nullable', 'string', 'max:255'],
            'eq-name' => ['nullable', 'string', 'max:255'],
            'notEq-name' => ['nullable', 'string', 'max:255'],
            'organization_code' => ['nullable', 'string', 'max:255'],
            'eq-organization_code' => ['nullable', 'string', 'max:255'],
            'panel_code' => ['nullable', 'string', 'max:255'],
            'eq-panel_code' => ['nullable', 'string', 'max:255'],
            'national_code' => ['nullable', 'string', 'max:255'],
            'eq-national_code' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(UserStatusEnum::class)],
            'eq-status' => ['nullable', Rule::enum(UserStatusEnum::class)],
            'notEq-status' => ['nullable', Rule::enum(UserStatusEnum::class)],
            'parent_type' => ['nullable', Rule::enum(CompanyParentEnum::class)],
            'eq-parent_type' => ['nullable', Rule::enum(CompanyParentEnum::class)],
            'min-waybills_count' => ['nullable', 'integer', 'min:0'],
            'max-waybills_count' => ['nullable', 'integer', 'min:0'],
            'eq-waybills_count' => ['nullable', 'integer', 'min:0'],
            'last_waybill_from' => ['nullable', 'date'],
            'last_waybill_to' => ['nullable', 'date', 'after_or_equal:last_waybill_from'],
            'paginate' => ['nullable', 'boolean'],
            'itemsPerPage' => ['nullable', 'integer', 'between:1,100'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
            'order_field' => ['nullable', Rule::in([
                'id', 'name', 'organization_code', 'panel_code', 'national_code',
                'status', 'created_at', 'waybills_count', 'last_waybill_at',
            ])],
            'order_type' => ['nullable', Rule::in(['asc', 'desc', 'ASC', 'DESC'])],
        ];
    }
}
