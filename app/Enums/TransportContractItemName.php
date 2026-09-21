<?php

namespace App\Enums;

enum TransportContractItemName: string
{
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

    public function label(): string
    {
        return match ($this) {
            self::BaseFreight => 'کرایه پایه', self::LoadingCost => 'هزینه بارگیری',
            self::WeighbridgeCost => 'هزینه باسکول', self::Warehousing => 'انبارداری',
            self::UnloadingCost => 'تخلیه بار', self::Commission => 'کمیسیون',
            self::ExcessTonnage => 'اضافه تناژ', self::InsurancePremium => 'حق بیمه',
            self::InsuranceVat => 'ارزش افزوده بیمه', self::AdvanceFreight => 'پیش‌کرایه',
        };
    }

    public function primaryValueLabel(): string
    {
        return match ($this) {
            self::BaseFreight => 'کرایه از هر تن',
            self::InsurancePremium => 'درصد از تخفیف',
            default => 'درصد از کرایه',
        };
    }

    public function secondaryValueLabel(): string
    {
        return match ($this) {
            self::BaseFreight => 'کرایه ثابت',
            self::InsurancePremium => 'مقدار ثابت از تخفیف',
            default => 'مقدار ثابت',
        };
    }

    /**
     * @return array{is_owned: bool, is_rental: bool, is_free: bool, is_unknown: bool, charge_recipient: bool, primary_value: bool, secondary_value: bool}
     */
    public function editableFields(): array
    {
        $typeFieldsAreEditable = $this !== self::BaseFreight;

        return [
            'is_owned' => $typeFieldsAreEditable,
            'is_rental' => $typeFieldsAreEditable,
            'is_free' => $typeFieldsAreEditable,
            'is_unknown' => $typeFieldsAreEditable,
            'charge_recipient' => true,
            'primary_value' => true,
            'secondary_value' => true,
        ];
    }
}
