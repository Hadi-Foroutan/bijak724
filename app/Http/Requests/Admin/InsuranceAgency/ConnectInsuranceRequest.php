<?php

namespace App\Http\Requests\Admin\InsuranceAgency;

use App\Http\Requests\BaseRequest;
use App\Models\Insurance;
use Illuminate\Validation\Rule;

class ConnectInsuranceRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'insurance_id' => ['required', 'integer', Rule::exists(Insurance::class, 'id')],
        ];
    }
}
