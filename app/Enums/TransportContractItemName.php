<?php

namespace App\Enums;

use App\Traits\EnumHelpers;

enum TransportContractItemName: string
{
    use EnumHelpers;

    case BaseFreight = 'base_freight';
    case LoadingCost = 'loading_cost';
    case WeighbridgeCost = 'weighbridge_cost';
    case Warehousing = 'warehousing';
    case UnloadingCost = 'unloading_cost';
    case Commission = 'commission';
    case ExcessTonnage = 'excess_tonnage';
    case InsurancePremium = 'insurance_premium';
    case InsuranceVat = 'insurance_vat';
    case AdvanceFreight = 'advance_freight';
    case Detention = 'detention';

    public function label(): string
    {
        return match ($this) {
            self::BaseFreight => 'کرایه پایه',
            self::LoadingCost => 'هزینه بارگیری',
            self::WeighbridgeCost => 'هزینه باسکول',
            self::Warehousing => 'انبارداری',
            self::UnloadingCost => 'تخلیه بار',
            self::Commission => 'کمیسیون',
            self::ExcessTonnage => 'اضافه تناژ',
            self::InsurancePremium => 'حق بیمه',
            self::InsuranceVat => 'مالیات بیمه',
            self::AdvanceFreight => 'پیش‌کرایه',
            self::Detention => 'حق توقف',
        };
    }

    public function fieldName(): string
    {
        return match ($this) {
            self::BaseFreight => 'base_freight_amount',
            self::AdvanceFreight => 'advance_freight_amount',
            self::WeighbridgeCost => 'weighbridge_amount',
            self::LoadingCost => 'loading_amount',
            self::Warehousing => 'warehousing_amount',
            self::Commission => 'commission_amount',
            self::InsurancePremium => 'insurance_amount',
            self::InsuranceVat => 'insurance_tax_amount',
            self::UnloadingCost => 'unloading_amount',
            self::ExcessTonnage => 'excess_tonnage_amount',
            self::Detention => 'detention_amount',
        };
    }

    public function primaryValueLabel(): string
    {
        return match ($this) {
            self::BaseFreight => 'کرایه هر تن',
            self::InsurancePremium => 'درصد تخفیف',
            self::InsuranceVat => 'درصد از حق بیمه',
            default => 'درصد از کرایه',
        };
    }

    public function secondaryValueLabel(): string
    {
        return match ($this) {
            self::BaseFreight => 'کرایه ثابت',
            self::InsurancePremium => 'مقدار ثابت تخفیف',
            default => 'مقدار ثابت',
        };
    }

    /**
     * @return array{is_owned: bool, is_rental: bool, is_free: bool, is_unknown: bool, charge_recipient: bool, primary_value: bool, secondary_value: bool}
     */
    public function editableFields(): array
    {
        $isBaseFreight = $this === self::BaseFreight;
        $isInsuranceVat = $this === self::InsuranceVat;

        return [
            'is_owned' => !$isBaseFreight /*&& !$isInsuranceVat*/,
            'is_rental' => !$isBaseFreight /*&& !$isInsuranceVat*/,
            'is_free' => !$isBaseFreight /*&& !$isInsuranceVat*/,
            'is_unknown' => !$isBaseFreight /*&& !$isInsuranceVat*/,
            'charge_recipient' => true,
            'primary_value' => !$isInsuranceVat,
            'secondary_value' => !$isInsuranceVat,
        ];
    }
}
