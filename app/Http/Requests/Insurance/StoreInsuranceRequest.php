<?php

namespace App\Http\Requests\Insurance;

use App\Enums\StatusEnum;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class StoreInsuranceRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'insurance_company_id' => ['required', 'integer', Rule::exists('insurance_companies', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'contract_number' => ['required', 'string', 'max:255', Rule::unique('insurances')->where('company_id', $this->companyId())],
            'is_default' => ['required', 'boolean'],
            'status' => ['required', Rule::enum(StatusEnum::class)],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string'],
            'representative_first_name' => ['nullable', 'string', 'max:255'],
            'representative_last_name' => ['nullable', 'string', 'max:255'],
            'representative_mobile' => ['nullable', 'string', 'max:30'],
            'representative_phone' => ['nullable', 'string', 'max:30'],
            'representative_fax' => ['nullable', 'string', 'max:30'],
            'representative_email' => ['nullable', 'email', 'max:255'],
            'representative_address' => ['nullable', 'string'],
        ];
    }
}
