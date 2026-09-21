<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\WaybillCargoRepositoryInterface;
use App\Models\Cargo;
use App\Models\Company\Waybill;
use App\Models\Company\WaybillCargo;
use App\Models\Packaging;
use Illuminate\Support\Arr;

/** @extends CompanyModelRepository<WaybillCargo> */
class WaybillCargoRepository extends CompanyModelRepository implements WaybillCargoRepositoryInterface
{
    private const FIELDS = [
        'cargo_id',
        'packaging_id',
        'product_owner_id',
        'description',
        'title',
        'origin_weight',
        'value',
        'quantity',
        'is_traffic',
        'is_returned',
        'cottage_number',
        'cottage_number_2',
        'driver_account_number',
        'container_number',
        'container_number_2',
    ];

    protected string $modelClass = WaybillCargo::class;

    public function syncForWaybill(Waybill $waybill, int $companyId, array $cargos): void
    {
        $cargos = $this->resolveReferenceIds($cargos);

        if ($this->hasSameCargos($waybill, $cargos)) {
            return;
        }

        $waybill->cargos()->delete();

        if ($cargos === []) {
            return;
        }

        $waybill->cargos()->createMany(array_map(
            fn (array $cargo): array => [...$cargo, 'owner_company_id' => $companyId],
            $cargos,
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $cargos
     * @return list<array<string, mixed>>
     */
    private function resolveReferenceIds(array $cargos): array
    {
        if ($cargos === []) {
            return [];
        }

        $cargoIdsByCode = Cargo::query()
            ->whereIn('code', array_column($cargos, 'cargo_id'))
            ->pluck('id', 'code');
        $packagingIdsByCode = Packaging::query()
            ->whereIn('code', array_column($cargos, 'packaging_id'))
            ->pluck('id', 'code');

        return array_map(static fn (array $cargo): array => [
            ...$cargo,
            'cargo_id' => isset($cargo['cargo_id']) ? $cargoIdsByCode[$cargo['cargo_id']] : null,
            'packaging_id' => isset($cargo['packaging_id']) ? $packagingIdsByCode[$cargo['packaging_id']] : null,
        ], $cargos);
    }

    /** @param list<array<string, mixed>> $cargos */
    private function hasSameCargos(Waybill $waybill, array $cargos): bool
    {
        $waybill->loadMissing('cargos');

        $currentCargos = $waybill->cargos
            ->map(fn (WaybillCargo $cargo): array => $this->normalize($cargo->only(self::FIELDS)))
            ->values()
            ->all();
        $newCargos = array_map(fn (array $cargo): array => $this->normalize($cargo), $cargos);

        return $currentCargos === $newCargos;
    }

    /** @param array<string, mixed> $cargo */
    private function normalize(array $cargo): array
    {
        $normalized = array_replace(
            array_fill_keys(self::FIELDS, null),
            Arr::only($cargo, self::FIELDS),
        );

        foreach (['cargo_id', 'packaging_id', 'product_owner_id', 'value', 'quantity'] as $field) {
            $normalized[$field] = $normalized[$field] === null ? null : (int) $normalized[$field];
        }

        $normalized['origin_weight'] = $normalized['origin_weight'] === null
            ? null
            : (float) $normalized['origin_weight'];
        $normalized['is_traffic'] = (bool) $normalized['is_traffic'];
        $normalized['is_returned'] = (bool) $normalized['is_returned'];

        return $normalized;
    }
}
