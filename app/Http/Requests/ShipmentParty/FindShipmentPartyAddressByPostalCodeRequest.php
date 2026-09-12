<?php

namespace App\Http\Requests\ShipmentParty;

use App\Http\Requests\BaseRequest;

class FindShipmentPartyAddressByPostalCodeRequest extends BaseRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'shipment_party_id' => ['required', 'integer'],
            'postal_code' => ['required', 'string', 'max:20'],
        ];
    }
}
