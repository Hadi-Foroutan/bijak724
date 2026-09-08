<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\WaybillRepositoryInterface;
use App\Models\Company\Waybill;
use Illuminate\Support\Arr;

class WaybillRepository extends CompanyModelRepository implements WaybillRepositoryInterface
{
    private const CARGO_FIELDS = [
        'cargo_id',
        'packaging_id',
        'title',
        'description',
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

    protected string $modelClass = Waybill::class;

    public function update(int $companyId, int $id, array $data): Waybill
    {
        unset($data['owner_company_id']);

        /** @var Waybill $waybill */
        $waybill = $this->findOrFail($companyId, $id);
        $waybill->fill($data);

        if ($waybill->isDirty()) {
            $waybill->save();
        }

        /** @var Waybill $waybill */
        $waybill = $this->loadRelations($waybill->refresh());

        return $waybill;
    }

    public function syncCargos(Waybill $waybill, int $companyId, array $cargos): void
    {
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

    /** @param array<int, array<string, mixed>> $cargos */
    private function hasSameCargos(Waybill $waybill, array $cargos): bool
    {
        $waybill->loadMissing('cargos');

        $currentCargos = $waybill->cargos
            ->map(fn ($cargo): array => $this->normalizeCargo($cargo->only(self::CARGO_FIELDS)))
            ->values()
            ->all();
        $newCargos = array_map(fn (array $cargo): array => $this->normalizeCargo($cargo), $cargos);

        return $currentCargos === $newCargos;
    }

    /** @param array<string, mixed> $cargo */
    private function normalizeCargo(array $cargo): array
    {
        $normalized = array_replace(
            array_fill_keys(self::CARGO_FIELDS, null),
            Arr::only($cargo, self::CARGO_FIELDS),
        );

        foreach (['cargo_id', 'packaging_id', 'value', 'quantity'] as $field) {
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
