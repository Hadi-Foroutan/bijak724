<?php

namespace App\Http\Requests\Driver;

use App\Http\Requests\BaseRequest;

class FindDriverByNationalCodeRequest extends BaseRequest
{
    /*protected function prepareForValidation(): void
    {
        $this->merge([
            'national_code' => $this->route('nationalCode'),
        ]);
    }*/

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'national_code' => ['required', 'string', 'size:10', 'regex:/^\d{10}$/'],
        ];
    }
}
