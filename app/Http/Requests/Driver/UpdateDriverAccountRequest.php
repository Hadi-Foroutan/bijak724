<?php

namespace App\Http\Requests\Driver;

use App\Http\Requests\BaseRequest;

class UpdateDriverAccountRequest extends BaseRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'sheba_number' => ['sometimes', 'required', 'string', 'max:34'],
            'bank_name' => ['sometimes', 'required', 'string', 'max:255'],
            'owner_name' => ['sometimes', 'required', 'string', 'max:255'],
            'is_default' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
