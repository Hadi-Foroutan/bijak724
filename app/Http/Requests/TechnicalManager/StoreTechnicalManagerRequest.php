<?php

namespace App\Http\Requests\TechnicalManager;

use App\Enums\StatusEnum;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class StoreTechnicalManagerRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'national_code' => [
                'required',
                'string',
                'digits:10',
                Rule::unique('technical_managers')->where('company_id', $this->companyId()),
            ],
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'status' => ['required', Rule::enum(StatusEnum::class)],
            'daily_limit' => ['required', 'integer', 'min:0', 'max:100000'],
        ];
    }
}
