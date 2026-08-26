<?php

namespace App\Http\Requests\ProductOwner;

use App\Http\Requests\BaseRequest;

class UpdateProductOwnerRequest extends BaseRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'national_code' => ['sometimes', 'nullable', 'string', 'max:20'],
        ];
    }
}
