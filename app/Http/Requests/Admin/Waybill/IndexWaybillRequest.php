<?php

namespace App\Http\Requests\Admin\Waybill;

use App\Enums\WaybillStatus;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class IndexWaybillRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'integer', Rule::exists('companies', 'id')],
            'company_name' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(WaybillStatus::class)],
            'created_at_from' => ['nullable', 'date'],
            'created_at_to' => ['nullable', 'date', 'after_or_equal:created_at_from'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'referral_number' => ['nullable', 'string', 'max:255'],
            'bijak_tracking_code' => ['nullable', 'string', 'max:255'],
            'paginate' => ['nullable', 'boolean'],
            'itemsPerPage' => ['nullable', 'integer', 'between:1,100'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
            'order_field' => ['nullable', Rule::in(['id', 'company_id', 'waybill_id', 'created_at', 'updated_at'])],
            'order_type' => ['nullable', Rule::in(['asc', 'desc', 'ASC', 'DESC'])],
        ];
    }
}
