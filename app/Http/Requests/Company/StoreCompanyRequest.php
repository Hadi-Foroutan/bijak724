<?php

namespace App\Http\Requests\Company;

use App\Enums\CompanyParentEnum;
use App\Enums\StatusEnum;
use App\Http\Requests\BaseRequest;
use App\Models\Company;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'parent_type' => [
                'required',
                Rule::enum(CompanyParentEnum::class),
            ],

            'parent_id' => [
                'nullable',
                'integer',
                'exists:companies,id',

                // اگر BRANCH بود → اجباری
                Rule::requiredIf(fn () => $this->parent_type === CompanyParentEnum::BRANCH->value),

                // اگر BRANCH نبود → نباید ارسال بشه
                Rule::prohibitedIf(fn () => $this->parent_type !== CompanyParentEnum::BRANCH->value),
            ],

            'organization_code' => ['required', 'string', 'max:255', Rule::unique(Company::class)],
            //            'panel_code' => ['required', 'string', 'max:255', Rule::unique(Company::class)],

            'name' => ['required', 'string', 'max:255'],

            'national_code' => ['required', 'string', 'max:20', Rule::unique(Company::class)],

            'contact_code1' => ['nullable', 'string', 'max:255'],
            'contact_code2' => ['nullable', 'string', 'max:255'],
            'contact_code3' => ['nullable', 'string', 'max:255'],

            'technical_contact_first_name' => ['nullable', 'string', 'max:255'],
            'technical_contact_last_name' => ['nullable', 'string', 'max:255'],
            'technical_contact_phone' => ['nullable', 'string', 'max:255'],

            'city_code' => ['required', 'integer', 'exists:cities,code'],
            'tel' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'fax' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],

            'logo' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],

            'status' => ['nullable', Rule::enum(StatusEnum::class)],

        ];
    }
}
