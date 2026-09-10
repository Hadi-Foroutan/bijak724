<?php

namespace App\Http\Requests\Insurance;

use App\Enums\StatusEnum;
use App\Http\Requests\BaseRequest;
use App\Models\Insurance;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateInsuranceRequest extends BaseRequest
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
            'insurance_company_id' => ['sometimes', 'required', 'integer', Rule::exists('insurance_companies', 'id')],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'contract_number' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('insurances')->where('company_id', $this->companyId())->ignore($this->route('insurance'))],
            'is_default' => ['sometimes', 'required', 'boolean'],
            'status' => ['sometimes', 'required', Rule::enum(StatusEnum::class)],
            'start_date' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'end_date' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'description' => ['sometimes', 'nullable', 'string'],
            'representative_first_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'representative_last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'representative_mobile' => ['sometimes', 'nullable', 'string', 'max:30'],
            'representative_phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'representative_fax' => ['sometimes', 'nullable', 'string', 'max:30'],
            'representative_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'representative_address' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $insurance = Insurance::query()
                ->where('company_id', $this->companyId())
                ->find($this->route('insurance'));

            if ($insurance === null) {
                return;
            }

            $startDate = $this->input('start_date', $insurance->start_date->format('Y-m-d'));
            $endDate = $this->input('end_date', $insurance->end_date->format('Y-m-d'));

            if (strtotime((string) $endDate) < strtotime((string) $startDate)) {
                $validator->errors()->add('end_date', 'تاریخ پایان باید بعد از یا مساوی تاریخ شروع باشد.');
            }
        });
    }
}
