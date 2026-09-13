<?php

namespace App\Http\Requests\ShipmentParty;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class FindShipmentPartyByNationalIdentifierRequest extends BaseRequest
{
    /*protected function prepareForValidation(): void
    {
        $this->merge([
            'national_identifier' => $this->route('nationalIdentifier'),
        ]);
    }*/

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'national_code' => ['required', 'string', 'regex:/^\d{10,11}$/'],
            'type' => ['required', 'string', Rule::in(['sender', 'receiver'])],
        ];
    }
}
