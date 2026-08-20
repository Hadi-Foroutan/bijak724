<?php

namespace App\Http\Requests\Driver;

use App\Enums\StatusEnum;
use App\Http\Requests\BaseRequest;
use App\Rules\NationalCodeRule;
use App\Services\Company\CompanyDataService;
use Illuminate\Validation\Rule;

class UpdateDriverRequest extends BaseRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $driverTable = app(CompanyDataService::class)->table($this->companyId(), 'drivers');

        return [
            'national_code' => [
                'sometimes',
                'required',
                'string',
                new NationalCodeRule,
                Rule::unique($driverTable, 'national_code')->ignore((int) $this->route('driver')),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'father_name' => ['sometimes', 'required', 'string', 'max:255'],
            'license_number' => ['sometimes', 'required', 'string', 'max:255'],
            'license_type' => ['sometimes', 'required', 'string', 'max:255'],
            'license_expiry_date' => ['sometimes', 'required', Rule::date()->format('Y-m-d')],
            'phone_number_1' => ['sometimes', 'nullable', 'string', 'max:20'],
            'phone_number_2' => ['sometimes', 'nullable', 'string', 'max:20'],
            'phone_number_3' => ['sometimes', 'nullable', 'string', 'max:20'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::enum(StatusEnum::class)],
        ];
    }

}
