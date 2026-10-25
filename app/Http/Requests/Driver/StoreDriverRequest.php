<?php

namespace App\Http\Requests\Driver;

use App\Enums\StatusEnum;
use App\Http\Requests\BaseRequest;
use App\Models\DriverLicenseType;
use Illuminate\Validation\Rule;
use App\Models\Company\Driver;

class StoreDriverRequest extends BaseRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'national_code' => [
                'required',
                'string',
                //                new NationalCodeRule,
                $this->uniqueInCompany(Driver::class, 'national_code'),
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'license_number' => ['nullable', 'string', 'max:255'],
            'license_type' => ['required', 'integer', Rule::exists(DriverLicenseType::class, 'id')],
            'license_expiry_date' => ['nullable', 'date'],
            'phone_number_1' => ['nullable', 'string', 'max:20'],
            'phone_number_2' => ['nullable', 'string', 'max:20'],
            'phone_number_3' => ['nullable', 'string', 'max:20'],
            'profile_image' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:' . config('company_uploads.image_max_size_kb', 5120),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::enum(StatusEnum::class)],
        ];
    }
}
