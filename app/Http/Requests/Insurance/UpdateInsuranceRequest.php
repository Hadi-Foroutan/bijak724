<?php

namespace App\Http\Requests\Insurance;

use App\Enums\StatusEnum;
use App\Http\Requests\BaseRequest;
use App\Models\Insurance;
use App\Rules\ActiveInsuranceAgencyCode;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateInsuranceRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            // The agency (the insurer's org_code and the agency code) is needed only by a contract that has none yet
            // (recorded before agencies existed); the others keep theirs, and change it by sending both.
            'insurance_company_org_code' => [
                Rule::requiredIf(fn(): bool => !$this->hasAgency() || $this->filled('insurance_agency_code')),
                'integer',
            ],
            'insurance_agency_code' => [
                Rule::requiredIf(fn(): bool => !$this->hasAgency() || $this->filled('insurance_company_org_code')),
                'string',
                'max:255',
                new ActiveInsuranceAgencyCode($this->input('insurance_company_org_code')),
            ],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'contract_number' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('insurances')->where('company_id', $this->companyId())->ignore($this->route('insurance'))],
            'is_default' => ['sometimes', 'required', 'boolean'],
            'status' => ['sometimes', 'required', Rule::enum(StatusEnum::class)],
            'start_date' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'end_date' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }

    private function hasAgency(): bool
    {
        return Insurance::query()
            ->where('company_id', $this->companyId())
            ->whereNotNull('insurance_agency_id')
            ->whereKey($this->route('insurance'))
            ->exists();
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

            if (strtotime((string)$endDate) < strtotime((string)$startDate)) {
                $validator->errors()->add('end_date', __('public.insurance_end_date_invalid'));
            }
        });
    }
}
