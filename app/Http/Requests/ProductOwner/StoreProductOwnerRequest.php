<?php

namespace App\Http\Requests\ProductOwner;

use App\Http\Requests\BaseRequest;

class StoreProductOwnerRequest extends BaseRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'national_code' => ['nullable', 'string', 'max:20'],
        ];
    }
}
