<?php

namespace App\Enums;

enum CompanySettingKey: string
{
    case AssignFirstAvailableWaybillNumber = 'general.assign_first_available_waybill_number';

    public function group(): string
    {
        return match ($this) {
            self::AssignFirstAvailableWaybillNumber => 'general',
        };
    }

    public function valueType(): SettingValueType
    {
        return match ($this) {
            self::AssignFirstAvailableWaybillNumber => SettingValueType::Boolean,
        };
    }

    public function defaultValue(): bool|int|float|string|null
    {
        return match ($this) {
            self::AssignFirstAvailableWaybillNumber => false,
        };
    }

    /** @return list<string> */
    public function validationRules(): array
    {
        return [
            'sometimes',
            $this->valueType()->validationRule(),
            ...match ($this) {
                self::AssignFirstAvailableWaybillNumber => [],
            },
        ];
    }
}
