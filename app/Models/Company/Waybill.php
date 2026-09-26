<?php

namespace App\Models\Company;

use App\Enums\WaybillStatus;
use App\Models\DynamicModel;
use App\Models\Insurance;
use App\Models\TransportContract;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Waybill extends DynamicModel
{
    protected string $companyTableKey = 'waybills';

    protected array $defaultRelations = [
        'sender', 'senderAddress.city', 'receiver', 'receiverAddress.city',
        'firstDriver', 'secondDriver', 'referralDriver', 'fleet',
        'transportContract', 'insurance.insuranceCompany',
        'cargos.cargo', 'cargos.packaging', 'cargos.productOwner',
    ];

    protected array $searchableFields = [
        'created_by',
        'sender_id',
        'sender_address_id',
        'sender_address_postal_code',
        'sender_address_phone',
        'sender_address_city_code',
        'sender_address_address',
        'sender_address_description',
        'sender_national_identifier',
        'sender_first_name',
        'sender_last_name',
        'sender_mobile',
        'receiver_id',
        'receiver_address_id',
        'receiver_address_postal_code',
        'receiver_address_phone',
        'receiver_address_city_code',
        'receiver_address_address',
        'receiver_address_description',
        'receiver_national_identifier',
        'receiver_first_name',
        'receiver_last_name',
        'receiver_mobile',
        'driver1_id',
        'driver1_national_code',
        'driver1_first_name',
        'driver1_last_name',
        'driver1_phone',
        'driver2_id',
        'driver2_national_code',
        'driver2_first_name',
        'driver2_last_name',
        'driver2_phone',
        'referral_driver_id',
        'referral_driver_national_code',
        'referral_driver_first_name',
        'referral_driver_last_name',
        'referral_driver_phone',
        'fleet_id',
        'referral_weight',
        'quantity',
        'loading_started_at',
        'loading_ended_at',
        'referral_number',
        'bijak_number',
        'serial_number',
        'issued_at',
        'liability_insurance',
        'bijak_tracking_code',
        'description',
        'status',
        'transport_contract_id',
        'base_freight_amount',
        'advance_freight_amount',
        'weighbridge_amount',
        'loading_amount',
        'warehousing_amount',
        'commission_amount',
        'insurance_amount',
        'insurance_tax_amount',
        'detention_amount',
        'driver_receivable_amount',
        'payable_amount',
        'freight_at_origin',
        'is_fixed',
        'fleet__plate_first_number',
        'fleet__plate_second_letter',
        'fleet__plate_third_number',
        'fleet__plate_fourth_number',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'referral_weight' => 'decimal:3',
            'loading_started_at' => 'datetime',
            'loading_ended_at' => 'datetime',
            'issued_at' => 'datetime',
            'status' => WaybillStatus::class,
            'freight_at_origin' => 'boolean',
            'is_fixed' => 'boolean',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsToCompany(ShipmentParty::class, 'sender_id', relationName: 'sender');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsToCompany(ShipmentParty::class, 'receiver_id', relationName: 'receiver');
    }

    public function senderAddress(): BelongsTo
    {
        return $this->belongsToCompany(
            ShipmentPartyAddress::class,
            'sender_address_id',
            relationName: 'senderAddress',
        );
    }

    public function receiverAddress(): BelongsTo
    {
        return $this->belongsToCompany(
            ShipmentPartyAddress::class,
            'receiver_address_id',
            relationName: 'receiverAddress',
        );
    }

    public function firstDriver(): BelongsTo
    {
        return $this->belongsToCompany(Driver::class, 'driver1_id', relationName: 'firstDriver');
    }

    public function secondDriver(): BelongsTo
    {
        return $this->belongsToCompany(Driver::class, 'driver2_id', relationName: 'secondDriver');
    }

    public function referralDriver(): BelongsTo
    {
        return $this->belongsToCompany(Driver::class, 'referral_driver_id', relationName: 'referralDriver');
    }

    public function fleet(): BelongsTo
    {
        return $this->belongsToCompany(Fleet::class, 'fleet_id', relationName: 'fleet');
    }

    public function transportContract(): BelongsTo
    {
        return $this->belongsTo(TransportContract::class);
    }

    public function insurance(): BelongsTo
    {
        return $this->belongsTo(Insurance::class, 'liability_insurance');
    }

    public function cargos(): HasMany
    {
        return $this->hasManyCompany(WaybillCargo::class, 'waybill_id');
    }
}
