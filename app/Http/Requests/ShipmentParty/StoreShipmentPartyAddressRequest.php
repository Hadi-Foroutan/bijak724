<?php

namespace App\Http\Requests\ShipmentParty;

use App\Http\Requests\BaseRequest;
use App\Interfaces\Company\ShipmentPartyAddressRepositoryInterface;
use App\Models\City;
use Illuminate\Validation\Rule;

class StoreShipmentPartyAddressRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(ShipmentPartyAddressRepositoryInterface $addressRepository): array
    {
        return [
            'postal_code' => [
                'required',
                'string',
                'max:20',
                $addressRepository->uniquePostalCodeForPartyRule(
                    $this->companyId(),
                    (int) $this->route('shipmentParty'),
                ),
            ],
            'phone' => ['nullable', 'string', 'max:11'],
            'city_code' => ['required', 'integer', Rule::exists(City::class, 'code')],
            'address' => ['required', 'string'],
            'description' => ['nullable', 'string'],
        ];
    }
}
