<?php

namespace App\Http\Requests\Fleet;

use App\Http\Requests\BaseRequest;

class FindFleetBySmartCardNumberRequest extends BaseRequest
{
    /*protected function prepareForValidation(): void
    {
        $this->merge([
            'smart_card_number' => $this->route('smartCardNumber'),
        ]);
    }*/

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'smart_card_number' => ['required', 'string', 'max:50', 'regex:/^\d+$/'],
        ];
    }
}
