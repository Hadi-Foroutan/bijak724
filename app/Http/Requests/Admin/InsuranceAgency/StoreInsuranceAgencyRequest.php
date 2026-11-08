<?php

namespace App\Http\Requests\Admin\InsuranceAgency;

use App\Enums\InsuranceAgencyType;
use App\Enums\InsuranceLevel;
use App\Enums\StatusEnum;
use App\Http\Requests\BaseRequest;
use App\Models\InsuranceAgency;
use App\Models\State;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Adding an agency: always under a province agent (`parent_id`, the supervision), from which it takes its insurer and state;
 * its city must be in that state. It is a legal agent (company name and company id code) or a natural one (first name,
 * last name and national code). The country and province agents are not added here (`generate-insurance-agencies`).
 */
class StoreInsuranceAgencyRequest extends BaseRequest
{
    public function rules(): array
    {
        $parent = $this->parentAgency($this->input('parent_id'));

        return [
            'parent_id' => ['required', 'integer', $this->provinceAgent()],
            'type' => ['required', Rule::enum(InsuranceAgencyType::class)],
            'city_code' => ['required', 'integer', $this->cityIn($parent?->state_code)],
            'code' => ['required', 'string', 'max:255', $this->uniqueCode($parent?->insurance_company_id)],
            ...$this->identityRules((string)$this->input('type'), ['required']),
            ...$this->contactRules(),
            'status' => ['sometimes', 'required', Rule::enum(StatusEnum::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.exists' => __('public.insurance_agency_parent_not_province'),
            'city_code.exists' => __('public.insurance_agency_city_outside_province'),
        ];
    }

    /**
     * The identity fields of the type are filled (with `$filled`); those of the other type are ignored and cleared.
     *
     * @param array<int, string> $filled
     * @return array<string, array<int, mixed>>
     */
    protected function identityRules(string $type, array $filled): array
    {
        $legal = fn(array $rules): array => $type === InsuranceAgencyType::Legal->value ? [...$filled, ...$rules] : ['sometimes', 'nullable', ...$rules];
        $natural = fn(array $rules): array => $type === InsuranceAgencyType::Natural->value ? [...$filled, ...$rules] : ['sometimes', 'nullable', ...$rules];

        return [
            'company_name' => $legal(['string', 'max:255']),
            'company_national_id' => $legal(['string', 'max:20']),
            'first_name' => $natural(['string', 'max:255']),
            'last_name' => $natural(['string', 'max:255']),
            'national_code' => $natural(['string', 'max:20']),
        ];
    }

    /** @return array<string, array<int, mixed>> */
    protected function contactRules(): array
    {
        return [
            'mobile' => ['sometimes', 'nullable', 'string', 'max:30'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'fax' => ['sometimes', 'nullable', 'string', 'max:30'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }

    protected function provinceAgent(): Exists
    {
        return Rule::exists('insurance_agencies', 'id')
            ->where('level', InsuranceLevel::Province->value)
            ->whereNull('deleted_at');
    }

    /** A city of the state (any city when there is no state, e.g. for a country agent or an unknown parent). */
    protected function cityIn(?int $stateCode): Exists
    {
        $rule = Rule::exists('cities', 'code');
        $stateId = $stateCode === null ? null : State::query()->where('code', $stateCode)->value('id');

        return $stateId === null ? $rule : $rule->where('state_id', $stateId);
    }

    protected function parentAgency(mixed $agencyId): ?InsuranceAgency
    {
        return is_numeric($agencyId) ? InsuranceAgency::query()->find((int)$agencyId) : null;
    }

    /** The code is unique among the agents of the same insurer: other insurers may use it too (deleted agents keep theirs). */
    protected function uniqueCode(?int $insuranceCompanyId, ?InsuranceAgency $ignore = null): mixed
    {
        return Rule::unique('insurance_agencies', 'code')
            ->where('insurance_company_id', $insuranceCompanyId)
            ->ignore($ignore);
    }
}
