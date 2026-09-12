<?php

namespace App\Http\Requests\Driver;

use App\Enums\StatusEnum;
use App\Http\Requests\BaseRequest;
use App\Interfaces\Company\DriverRepositoryInterface;
use App\Models\DriverLicenseType;
use App\Rules\NationalCodeRule;
use Illuminate\Validation\Rule;

class UpdateDriverRequest extends BaseRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(DriverRepositoryInterface $driverRepository): array
    {
        return [
            'national_code' => [
                'sometimes',
                'required',
                'string',
                new NationalCodeRule,
                $driverRepository->uniqueNationalCodeRule(
                    $this->companyId(),
                    (int) $this->route('driver'),
                ),
            ],
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'father_name' => ['sometimes', 'required', 'string', 'max:255'],
            'license_number' => ['sometimes', 'required', 'string', 'max:255'],
            'license_type' => ['sometimes', 'required', 'integer', Rule::exists(DriverLicenseType::class, 'id')],
            'license_expiry_date' => ['sometimes', 'required', Rule::date()->format('Y-m-d')],
            'phone_number_1' => ['sometimes', 'nullable', 'string', 'max:20'],
            'phone_number_2' => ['sometimes', 'nullable', 'string', 'max:20'],
            'phone_number_3' => ['sometimes', 'nullable', 'string', 'max:20'],
            'profile_image' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.config('company_uploads.image_max_size_kb', 5120),
            ],
            'remove_profile_image' => ['sometimes', 'boolean'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::enum(StatusEnum::class)],
        ];
    }
}
