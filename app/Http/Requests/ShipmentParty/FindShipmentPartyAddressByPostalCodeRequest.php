<?php

namespace App\Http\Requests\ShipmentParty;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class FindShipmentPartyAddressByPostalCodeRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'postal_code' => ['required', 'string', 'max:20'],
            'type' => ['required', 'string', Rule::in(['sender', 'receiver'])],
        ];
    }
}
