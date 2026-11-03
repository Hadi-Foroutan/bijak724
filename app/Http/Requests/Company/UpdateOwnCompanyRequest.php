<?php

namespace App\Http\Requests\Company;

use App\Http\Requests\BaseRequest;
use App\Models\Company;
use Illuminate\Validation\Rule;

/**
 * What a company may change about itself. The parent, the type, the status, the panel code and the other
 * administrative data are not here: only the admin changes those.
 */
class UpdateOwnCompanyRequest extends BaseRequest
{
    public function rules(): array
    {
        $company = $this->company();
        $image = [
            'sometimes',
            'nullable',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:' . config('company_uploads.image_max_size_kb', 5120),
        ];

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'organization_code' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique(Company::class)->ignore($company),
            ],
            'national_code' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique(Company::class)->ignore($company),
            ],

            'technical_contact_first_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'technical_contact_last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'technical_contact_phone' => ['sometimes', 'nullable', 'string', 'max:255'],

            'city_code' => ['sometimes', 'required', 'integer', 'exists:cities,code'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:20'],
            'address' => ['sometimes', 'nullable', 'string'],
            'tel' => ['sometimes', 'nullable', 'string', 'max:255'],
            'fax' => ['sometimes', 'nullable', 'string', 'max:255'],
            'website' => ['sometimes', 'nullable', 'string', 'max:255'],

            'contact_code1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contact_code2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contact_code3' => ['sometimes', 'nullable', 'string', 'max:255'],

            'description' => ['sometimes', 'nullable', 'string'],

            'logo' => $image,
            'remove_logo' => ['sometimes', 'boolean'],
            'signature' => $image,
            'remove_signature' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeBooleanStrings(['remove_logo', 'remove_signature']);
    }
}
