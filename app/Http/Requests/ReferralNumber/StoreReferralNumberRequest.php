<?php

namespace App\Http\Requests\ReferralNumber;

use App\Enums\ReferralNumberStatus;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class StoreReferralNumberRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'serial_number' => ['required', 'string', 'max:255'],
            'from_number' => ['required', 'integer', 'min:1'],
            'to_number' => ['required', 'integer', 'gte:from_number'],
            'last_number' => ['nullable', 'integer', 'min:0'],
            'status' => ['sometimes', 'required', Rule::enum(ReferralNumberStatus::class)],
        ];
    }
}
