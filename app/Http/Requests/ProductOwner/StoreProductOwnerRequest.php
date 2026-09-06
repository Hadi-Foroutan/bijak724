<?php

namespace App\Http\Requests\ProductOwner;

use App\Http\Requests\BaseRequest;

class StoreProductOwnerRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'transportation_code' => ['required', 'string', 'max:255'],
        ];
    }
}
