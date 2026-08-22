<?php

use App\Enums\UserStatusEnum;

return [
    'waybills' => [
        ['name' => 'tracking_code', 'type' => 'string'],
        ['name' => 'company_code', 'type' => 'integer'],
        ['name' => 'sender_id', 'type' => 'integer'],
        ['name' => 'receiver_id', 'type' => 'integer'],
        ['name' => 'driver1_id', 'type' => 'integer'],
        ['name' => 'driver2_id', 'type' => 'integer'],
        ['name' => 'fleet_id', 'type' => 'integer'],
        ['name' => 'packaging_id', 'type' => 'integer'],
        ['name' => 'product_owner_id', 'type' => 'integer'],
        ['name' => 'meta', 'type' => 'json'],
    ],
    'drivers' => [
        ['name' => 'national_code', 'type' => 'string', 'nullable' => false],
        ['name' => 'first_name', 'type' => 'string', 'nullable' => false],
        ['name' => 'last_name', 'type' => 'string', 'nullable' => false],
        ['name' => 'father_name', 'type' => 'string', 'nullable' => false],
        ['name' => 'license_number', 'type' => 'string', 'nullable' => false],
        ['name' => 'license_type', 'type' => 'string', 'nullable' => false],
        ['name' => 'license_expiry_date', 'type' => 'date', 'nullable' => false],
        ['name' => 'phone_number_1', 'type' => 'string'],
        ['name' => 'phone_number_2', 'type' => 'string'],
        ['name' => 'phone_number_3', 'type' => 'string'],
        ['name' => 'description', 'type' => 'text'],
        [
            'name' => 'status',
            'type' => 'enum',
            'values' => UserStatusEnum::values(),
            'default' => UserStatusEnum::ACTIVE->value,
            'nullable' => false,
        ],
    ],
    'sender_receivers' => [
        ['name' => 'name', 'type' => 'string'],
        ['name' => 'national_code', 'type' => 'string'],
    ],
    'addresses' => [
        ['name' => 'name', 'type' => 'string'],
        ['name' => 'national_code', 'type' => 'string'],
    ],
    'fleets' => [
        ['name' => 'name', 'type' => 'string'],
        ['name' => 'national_code', 'type' => 'string'],
    ],
    'cargos' => [
        ['name' => 'name', 'type' => 'string'],
        ['name' => 'national_code', 'type' => 'string'],
    ],
    'product_owner' => [
        ['name' => 'name', 'type' => 'string'],
        ['name' => 'national_code', 'type' => 'string'],
    ],
];
