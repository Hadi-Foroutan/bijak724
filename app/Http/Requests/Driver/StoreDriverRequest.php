<?php

namespace App\Http\Requests\Driver;

use App\Enums\StatusEnum;
use App\Http\Requests\BaseRequest;
use App\Rules\NationalCodeRule;
use App\Services\Company\CompanyDataService;
use Illuminate\Validation\Rule;

class StoreDriverRequest extends BaseRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $driverTable = app(CompanyDataService::class)->table($this->companyId(), 'drivers');

        return [
            'national_code' => [
                'required',
                'string',
//                new NationalCodeRule,
                Rule::unique($driverTable, 'national_code'),
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'father_name' => ['required', 'string', 'max:255'],
            'license_number' => ['required', 'string', 'max:255'],
            'license_type' => ['required', 'string', 'max:255'],
            'license_expiry_date' => ['required', Rule::date()->format('Y-m-d')],
            'phone_number_1' => ['nullable', 'string', 'max:20'],
            'phone_number_2' => ['nullable', 'string', 'max:20'],
            'phone_number_3' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::enum(StatusEnum::class)],
        ];
    }

}
