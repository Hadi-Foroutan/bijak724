<?php

namespace App\Http\Requests\Fleet;

use App\Http\Requests\BaseRequest;

class FindFleetByPlateRequest extends BaseRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'plate_first_number' => ['required', 'string', 'regex:/^\d{2}$/'],
            'plate_second_letter' => ['required', 'string', 'min:1', 'max:3'],
            'plate_third_number' => ['required', 'string', 'regex:/^\d{3}$/'],
            'plate_fourth_number' => ['required', 'string', 'regex:/^\d{2}$/'],
        ];
    }
}
