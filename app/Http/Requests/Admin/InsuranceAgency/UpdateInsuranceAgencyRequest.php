<?php

namespace App\Http\Requests\Admin\InsuranceAgency;

use App\Enums\InsuranceAgencyType;
use App\Enums\InsuranceLevel;
use App\Enums\StatusEnum;
use App\Models\InsuranceAgency;
use Illuminate\Validation\Rule;

/**
 * Editing an agent's own data (only the fields sent). The generated country and province agents start with a name only,
 * so their other fields may stay empty; an agency keeps its fields required, may change its type (then the identity
 * fields of the new type are required) and may move to another province agent (then its city must be sent again, inside
 * the new province). A country agent stays legal. The level, insurer and state are never edited directly.
 */
class UpdateInsuranceAgencyRequest extends StoreInsuranceAgencyRequest
{
    public function rules(): array
    {
        /** @var InsuranceAgency $agency */
        $agency = $this->route('insuranceAgency');
        $isAgency = $agency->level === InsuranceLevel::Agency;
        $changesType = $this->has('type') && $this->input('type') !== $agency->type->value;
        $movesProvince = $isAgency && $this->has('parent_id');
        $province = $movesProvince ? $this->parentAgency($this->input('parent_id')) : $agency;

        $filled = $isAgency ? ['sometimes', 'required'] : ['sometimes', 'nullable'];
        $typeRule = $agency->level === InsuranceLevel::Country ? Rule::in([InsuranceAgencyType::Legal->value]) : Rule::enum(InsuranceAgencyType::class);

        return [
            ...($isAgency ? ['parent_id' => ['sometimes', 'required', 'integer', $this->provinceAgent()]] : []),
            'type' => ['sometimes', 'required', $typeRule],
            'city_code' => [...($movesProvince ? ['required'] : $filled), 'integer', $this->cityIn($province?->state_code)],
            'code' => [...$filled, 'string', 'max:255', $this->uniqueCode($province?->insurance_company_id, $agency)],
            ...$this->identityRules((string)$this->input('type', $agency->type->value), $isAgency && $changesType ? ['required'] : $filled),
            ...$this->contactRules(),
            'status' => ['sometimes', 'required', Rule::enum(StatusEnum::class)],
        ];
    }
}
