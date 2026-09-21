<?php

namespace App\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class TreeResource extends JsonResource
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function treeData(Request $request, string $label, array $attributes): array
    {
        return [
            'id' => (string) $this->tree_path,
            'label' => $label,
            'id2' => $this->resource->getKey(),
            ...$attributes,
            'children' => $this->relationLoaded('children')
                ? $this->children
                    ->map(fn (Model $model): array => static::make($model)->resolve($request))
                    ->values()
                    ->all()
                : [],
        ];
    }
}
