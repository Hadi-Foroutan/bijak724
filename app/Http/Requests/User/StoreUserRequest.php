<?php

namespace App\Http\Requests\User;

use App\Enums\UserStatusEnum;
use App\Enums\RoleEnum;
use App\Http\Requests\BaseRequest;
use App\Models\Role;
use App\Rules\CompanyRequiredForRole;
use Illuminate\Validation\Rule;

class StoreUserRequest extends BaseRequest
{
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'company_id' => [
                'nullable',
                'exists:companies,id',
                new CompanyRequiredForRole,
            ],

            'parent_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],

            'first_name' => [
                'required',
                'string',
            ],

            'last_name' => [
                'required',
                'string',
            ],

            'role_id' => [
                'required',
                'exists:roles,id',
            ],

            'print_name' => [
                'nullable',
                'string',
            ],

            'phone' => [
                'nullable',
                'string',
                Rule::unique('users', 'phone')->ignore($user),
            ],

            'national_code' => [
                'required',
                'string',
                Rule::unique('users', 'national_code')->ignore($user),
                //                new NationalCodeRule(),
            ],

            'email' => [
                'nullable',
                'email',
                Rule::unique('users', 'email')->ignore($user),
            ],

            'username' => [
                'required',
                'string',
                Rule::unique('users', 'username')->ignore($user),
            ],

            'password' => [
                Rule::requiredIf(is_null($user)),
                'nullable',
                'string',
                'min:7',
            ],

            'min_commission_percentage' => [
                'required',
                'numeric',
                'min:0',
            ],

            'max_commission_percentage' => [
                'required',
                'numeric',
                'min:0',
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'signature_image' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:' . config('company_uploads.image_max_size_kb', 5120),
            ],

            'profile_image' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:' . config('company_uploads.image_max_size_kb', 5120),
            ],

            'remove_profile_image' => ['sometimes', 'boolean'],
            'is_profile_delete' => ['sometimes', 'boolean'],

            'remove_signature_image' => ['sometimes', 'boolean'],
            'is_signature_delete' => ['sometimes', 'boolean'],

            'description' => [
                'nullable',
                'string',
            ],

            ...$this->insuranceRules(),

            'status' => [
                'required',
                'string',
                'in:' . implode(',', UserStatusEnum::values()),
            ],
        ];
    }

    /**
     * The insurance role is linked to one insurance agency, and that is all it takes: the insurer, the city and the level
     * are data of the agency, not of the user. No other role accepts an agency.
     *
     * @return array<string, array<int, mixed>>
     */
    private function insuranceRules(): array
    {
        if (!$this->isInsuranceRole()) {
            return ['insurance_agency_id' => ['prohibited']];
        }

        return [
            'insurance_agency_id' => [
                'required',
                'integer',
                Rule::exists('insurance_agencies', 'id')->whereNull('deleted_at'),
            ],
        ];
    }

    private function isInsuranceRole(): bool
    {
        return Role::query()->whereKey($this->input('role_id'))->where('name', RoleEnum::INSURANCE->value)->exists();
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeBooleanStrings([
            'remove_profile_image',
            'is_profile_delete',
            'remove_signature_image',
            'is_signature_delete',
        ]);
    }
}
