<?php

namespace App\Http\Requests\Company;

use App\Enums\CompanyParentEnum;
use App\Enums\StatusEnum;
use App\Http\Requests\BaseRequest;
use App\Models\Company;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends BaseRequest
{
    public function rules(): array
    {
        /** @var Company $company */
        $company = $this->route('company');

        return [
            'parent_type' => [
                'sometimes',
                'required',
                Rule::enum(CompanyParentEnum::class),
            ],

            'parent_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists(Company::class, 'id')->where(
                    fn ($query) => $query
                        ->where('parent_type', CompanyParentEnum::ORIGINAL->value)
                        ->whereNull('parent_id')
                        ->whereNull('deleted_at'),
                ),

                Rule::requiredIf(fn () => $this->isBranch() && $this->has('parent_type')),
                Rule::prohibitedIf(fn () => ! $this->isBranch() && $this->has('parent_id')),
            ],

            /*'code' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique(Company::class)->ignore($company),
            ],*/

            'organization_code' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique(Company::class)->ignore($company),
            ],

            'name' => ['sometimes', 'required', 'string', 'max:255'],

            'national_code' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique(Company::class)->ignore($company),
            ],

            'contact_code1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contact_code2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contact_code3' => ['sometimes', 'nullable', 'string', 'max:255'],

            'technical_contact_first_name' => ['nullable', 'string', 'max:255'],
            'technical_contact_last_name' => ['nullable', 'string', 'max:255'],
            'technical_contact_phone' => ['nullable', 'string', 'max:255'],

            'city_code' => ['sometimes', 'nullable', 'integer', 'exists:cities,code'],
            'tel' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:20'],
            'fax' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],

            'brand' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],

            'status' => ['sometimes', 'required', Rule::enum(StatusEnum::class)],
        ];
    }

    private function isBranch(): bool
    {
        $type = $this->input('parent_type');

        // اگر ارسال نشده، مقدار فعلی مدل رو در نظر بگیر
        if (! $type) {
            /** @var Company $company */
            $company = $this->route('company');

            return $company->parent_type === CompanyParentEnum::BRANCH->value;
        }

        return $type === CompanyParentEnum::BRANCH->value;
    }
}
