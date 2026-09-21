<?php

namespace App\Http\Requests\ProductOwner;

use App\Http\Requests\BaseRequest;

class UpdateProductOwnerRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:20'],
            'transportation_code' => ['sometimes', 'required', 'string', 'max:255'],
        ];
    }
}
