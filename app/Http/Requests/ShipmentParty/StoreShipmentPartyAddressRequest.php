<?php

namespace App\Http\Requests\ShipmentParty;

use App\Http\Requests\BaseRequest;
use App\Models\City;
use Illuminate\Validation\Rule;
use App\Models\Company\ShipmentPartyAddress;

class StoreShipmentPartyAddressRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'postal_code' => [
                'required',
                'string',
                'max:20',
                $this->uniqueInCompany(ShipmentPartyAddress::class, 'postal_code')
                    ->where('shipment_party_id', $this->routeModelId('shipmentParty')),
            ],
            'phone' => ['nullable', 'string', 'max:11'],
            'city_code' => ['required', 'integer', Rule::exists(City::class, 'code')],
            'address' => ['required', 'string'],
            'description' => ['nullable', 'string'],
        ];
    }
}
