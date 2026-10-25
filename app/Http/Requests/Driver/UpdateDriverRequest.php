<?php

namespace App\Http\Requests\Driver;

use App\Enums\StatusEnum;
use App\Http\Requests\BaseRequest;
use App\Models\DriverLicenseType;
use Illuminate\Validation\Rule;
use App\Models\Company\Driver;

class UpdateDriverRequest extends BaseRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'national_code' => [
                'sometimes',
                'required',
                'string',
                //                new NationalCodeRule,
                $this->uniqueInCompany(Driver::class, 'national_code', $this->routeModelId('driver')),
            ],
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'father_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'license_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'license_type' => ['sometimes', 'required', 'integer', Rule::exists(DriverLicenseType::class, 'id')],
            'license_expiry_date' => ['sometimes', 'nullable', 'date'],
            'phone_number_1' => ['sometimes', 'nullable', 'string', 'max:20'],
            'phone_number_2' => ['sometimes', 'nullable', 'string', 'max:20'],
            'phone_number_3' => ['sometimes', 'nullable', 'string', 'max:20'],
            'profile_image' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:' . config('company_uploads.image_max_size_kb', 5120),
            ],
            'remove_profile_image' => ['sometimes', 'boolean'],
            'is_profile_delete' => ['sometimes', 'boolean'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::enum(StatusEnum::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeBooleanStrings(['remove_profile_image', 'is_profile_delete']);
    }
}
