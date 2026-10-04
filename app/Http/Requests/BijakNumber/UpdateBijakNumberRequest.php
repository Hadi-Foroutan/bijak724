<?php

namespace App\Http\Requests\BijakNumber;

use App\Enums\BijakNumberStatus;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class UpdateBijakNumberRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'serial_number' => ['sometimes', 'required', 'string', 'max:255'],
            'from_number' => ['sometimes', 'required', 'integer', 'min:1'],
            'to_number' => ['sometimes', 'required', 'integer', 'min:1'],
            'last_number' => ['prohibited'],
            'status' => ['sometimes', 'required', Rule::enum(BijakNumberStatus::class)],
        ];
    }
}
