<?php

namespace App\Http\Requests\TechnicalManager;

use App\Enums\StatusEnum;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class UpdateTechnicalManagerRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'national_code' => [
                'sometimes',
                'required',
                'string',
                'digits:10',
                Rule::unique('technical_managers')
                    ->where('company_id', $this->companyId())
                    ->ignore($this->route('technicalManager')),
            ],
            'mobile' => ['sometimes', 'required', 'string', 'regex:/^09\d{9}$/'],
            'status' => ['sometimes', 'required', Rule::enum(StatusEnum::class)],
            'daily_limit' => ['sometimes', 'required', 'integer', 'min:0', 'max:100000'],
        ];
    }
}
