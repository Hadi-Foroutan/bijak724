<?php

namespace App\Http\Requests\Driver;

use App\Enums\StatusEnum;
use App\Http\Requests\BaseRequest;
use App\Interfaces\Company\DriverRepositoryInterface;
use App\Models\DriverLicenseType;
use App\Rules\NationalCodeRule;
use Illuminate\Validation\Rule;

class StoreDriverRequest extends BaseRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(DriverRepositoryInterface $driverRepository): array
    {
        return [
            'national_code' => [
                'required',
                'string',
                //                new NationalCodeRule,
                $driverRepository->uniqueNationalCodeRule($this->companyId()),
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'father_name' => ['required', 'string', 'max:255'],
            'license_number' => ['required', 'string', 'max:255'],
            'license_type' => ['required', 'integer', Rule::exists(DriverLicenseType::class, 'id')],
            'license_expiry_date' => ['required', Rule::date()->format('Y-m-d')],
            'phone_number_1' => ['nullable', 'string', 'max:20'],
            'phone_number_2' => ['nullable', 'string', 'max:20'],
            'phone_number_3' => ['nullable', 'string', 'max:20'],
            'profile_image' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.config('company_uploads.image_max_size_kb', 5120),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::enum(StatusEnum::class)],
        ];
    }
}
