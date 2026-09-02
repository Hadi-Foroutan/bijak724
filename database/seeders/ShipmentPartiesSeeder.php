<?php

namespace Database\Seeders;

use App\Enums\StatusEnum;
use App\Services\Company\ShipmentParty\ShipmentPartyService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShipmentPartiesSeeder extends Seeder
{
    public function __construct(protected ShipmentPartyService $shipmentPartyService) {}

    public function run(): void
    {
        $shipments = [
            [
                'national_identifier' => '1870675274',
                'type' => 'sender',
                'status' => StatusEnum::ACTIVE->value,
                'title' => 'test',
                'first_name' => 'test',
                'last_name' => 'test',
                'mobile' => '09934142558',
                'landline' => '0318498489',
                'intermediary_code' => 'test',
                'transportation_code' => 'test',
                'email' => 'sfdsgdf@gmail.com'
            ],
        ];

        foreach ($shipments as $shipment) {
            $this->shipmentPartyService->create(1000,$shipment);
        }
    }
}
