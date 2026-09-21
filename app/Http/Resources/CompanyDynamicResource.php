<?php

namespace App\Http\Resources;

use App\Models\DynamicModel;
use App\Services\Company\CompanyTableRegistry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

abstract class CompanyDynamicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof DynamicModel) {
            throw new LogicException('Company dynamic resources require a DynamicModel.');
        }

        $fieldNames = collect(app(CompanyTableRegistry::class)->columns($this->resource->companyTableKey()))
            ->filter(fn (array $column): bool => (bool) ($column['api'] ?? true))
            ->pluck('name')
            ->all();

        return [
            'id' => $this->resource->getKey(),
            ...$this->resource->only($fieldNames),
            ...$this->relations($request),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /** @return array<string, mixed> */
    protected function relations(Request $request): array
    {
        return [];
    }
}
