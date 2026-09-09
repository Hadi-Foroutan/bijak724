<?php

namespace App\Http\Requests\ShipmentParty;

use App\Http\Requests\BaseRequest;

class FindShipmentPartyByNationalIdentifierRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'national_identifier' => $this->route('nationalIdentifier'),
        ]);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'national_identifier' => ['required', 'string', 'regex:/^\d{10,11}$/'],
        ];
    }
}
