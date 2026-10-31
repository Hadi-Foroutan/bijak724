<?php

namespace App\Services\Company\Report\Templates;

use App\Enums\WaybillStatus;
use App\Models\Company\Waybill;
use App\Support\PlateFormat;
use Closure;
use LogicException;

/**
 * Every field a ready-made report can show, by name. A report type (config/report_templates.php) is just a list of
 * these names in the order of its columns, so a new report type needs no code unless it needs a field that is not here:
 * add that field once below and every type can use it.
 */
class TemplateFieldLibrary
{
    /** @var array<string, TemplateColumn>|null */
    private ?array $fields = null;

    public function get(string $name): TemplateColumn
    {
        return $this->all()[$name] ?? throw new LogicException("Report field [{$name}] is not defined in " . self::class . '.');
    }

    /** @return array<string, TemplateColumn> */
    public function all(): array
    {
        return $this->fields ??= collect([...$this->summaryMetrics(), ...$this->tableFields()])
            ->keyBy(fn(TemplateColumn $column): string => $column->key)
            ->all();
    }

    /**
     * Metrics for the `summary` layout: each is one number for the whole period (a waybill adds its share).
     * Money and weights only count the waybills that are not canceled.
     *
     * @return list<TemplateColumn>
     */
    private function summaryMetrics(): array
    {
        $valid = fn(Waybill $waybill): bool => $waybill->status !== WaybillStatus::Canceled;
        $sumOf = fn(string $path): Closure => fn(Waybill $waybill): int|float => $valid($waybill) ? (float)data_get($waybill, $path, 0) : 0;
        $metric = fn(string $key, string $label, Closure $amount, array $relations = []): TemplateColumn => new TemplateColumn(
            $key, $label, TemplateColumn::NUMBER, resolver: $amount, relations: $relations, align: 'center', width: 110,
        );

        return [
            $metric('count_all', 'تعداد کل', fn(Waybill $waybill): int => 1),
            $metric('count_valid', 'تعداد سالم', fn(Waybill $waybill): int => (int)$valid($waybill)),
            $metric('count_canceled', 'تعداد باطله', fn(Waybill $waybill): int => (int)!$valid($waybill)),
            $metric('total_commission', 'مجموع کمسیون شرکت', $sumOf('commission_amount')),
            $metric('total_base_freight', 'مجموع کرایه پایه', $sumOf('base_freight_amount')),
            $metric('total_loading', 'مجموع بارگیری', $sumOf('loading_amount')),
            $metric('total_weighbridge', 'مجموع باسکول', $sumOf('weighbridge_amount')),
            $metric('total_premium', 'مجموع حق بیمه', $sumOf('insurance_amount')),
            $metric('total_vat', 'ارزش افزوده بیمه', $sumOf('insurance_tax_amount')),
            $metric('total_premium_vat', 'مجموع بیمه و ارزش افزوده', fn(Waybill $waybill): int|float => $sumOf('insurance_amount')($waybill) + $sumOf('insurance_tax_amount')($waybill)),
            $metric('total_driver_receivable', 'دریافتی از راننده', $sumOf('driver_receivable_amount')),
            $metric('total_cargo_value', 'مجموع ارزش محموله', fn(Waybill $waybill): int|float => $valid($waybill) ? $waybill->cargos->sum('value') : 0, ['cargos']),
            $metric('total_origin_weight', 'مجموع وزن مبدا', fn(Waybill $waybill): int|float => $valid($waybill) ? $waybill->cargos->sum('origin_weight') : 0, ['cargos']),
        ];
    }

    /**
     * Columns for the `table` layout (one row per waybill, or per group with `group_by`).
     *
     * @return list<TemplateColumn>
     */
    private function tableFields(): array
    {
        // The weight of a waybill: what was loaded, from the origin weights of its cargos.
        $weight = fn(Waybill $waybill): int|float => $waybill->cargos->sum('origin_weight');

        return [
            new TemplateColumn('row', 'ردیف', TemplateColumn::INDEX, width: 35),
            new TemplateColumn('date', 'تاریخ', TemplateColumn::DATE, path: 'issued_at', width: 62),
            new TemplateColumn('bijak_number', 'شماره بارنامه', path: 'bijak_number', width: 70),
            new TemplateColumn('serial', 'سری', path: 'serial_number', width: 45),
            new TemplateColumn('destination', 'مقصد', path: 'receiverAddress.city.name', relations: ['receiverAddress.city'], width: 80),
            new TemplateColumn('cargo', 'محموله', path: 'cargos.*.cargo.name', relations: ['cargos.cargo'], width: 80),
            new TemplateColumn('loading_type', 'نوع بارگیر', path: 'fleet.loadingType.name', relations: ['fleet.loadingType'], width: 90),
            new TemplateColumn('plate', 'پلاک', TemplateColumn::PLATE, resolver: fn(Waybill $waybill): ?string => PlateFormat::text($waybill->fleet), relations: ['fleet'], width: 110),
            new TemplateColumn(
                'insurance',
                'نام / شماره بیمه',
                resolver: fn(Waybill $waybill): string => trim(
                    data_get($waybill, 'insurance.insuranceCompany.name', '') . "\n" . data_get($waybill, 'insurance.contract_number', '')
                ),
                relations: ['insurance.insuranceCompany'],
                width: 120,
                multiline: true,
            ),
            new TemplateColumn('cargo_value', 'ارزش', TemplateColumn::NUMBER, resolver: fn(Waybill $waybill): int|float => $waybill->cargos->sum('value'), relations: ['cargos'], width: 85, sum: true),
            new TemplateColumn('premium', 'بیمه', TemplateColumn::NUMBER, path: 'insurance_amount', width: 70, sum: true),
            new TemplateColumn('waybill_count', 'تعداد', TemplateColumn::NUMBER, width: 60, sum: true, aggregate: TemplateColumn::COUNT),
            new TemplateColumn('weight_sum', 'جمع وزن', TemplateColumn::NUMBER, resolver: $weight, relations: ['cargos'], width: 85, sum: true, aggregate: TemplateColumn::SUM),
            new TemplateColumn('weight_avg', 'میانگین وزن', TemplateColumn::NUMBER, resolver: $weight, relations: ['cargos'], width: 85, aggregate: TemplateColumn::AVG, decimals: 2),
            new TemplateColumn('vat', 'مالیات ارزش افزوده', TemplateColumn::NUMBER, path: 'insurance_tax_amount', width: 70, sum: true),
        ];
    }
}
