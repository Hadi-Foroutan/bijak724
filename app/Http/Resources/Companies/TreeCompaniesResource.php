<?php

namespace App\Http\Resources\Companies;

use App\Http\Resources\CompanyResource;
use App\Http\Resources\TreeResource;
use Illuminate\Http\Request;

class TreeCompaniesResource extends TreeResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $attributes = CompanyResource::make($this->resource)->resolve($request);
        unset($attributes['id']);

        return $this->treeData($request, $this->name, $attributes);
    }
}
