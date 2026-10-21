<?php

namespace Database\Seeders;

use App\Services\Company\ShipmentParty\ShipmentPartyAddressService;
use Illuminate\Database\Seeder;

class AddressesSeeder extends Seeder
{
    public function __construct(protected ShipmentPartyAddressService $addressService)
    {
    }

    public function run(): void
    {
        $addresses = [
            [
                'postal_code' => '4648548',
                'city_code' => '11320000',
                'address' => 'fjhfgjghkhg'
            ],
        ];

        foreach ($addresses as $address) {
            $this->addressService->create(1000, 1, $address);
        }
    }
}
