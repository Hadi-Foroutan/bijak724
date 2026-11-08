<?php

namespace App\Http\Resources\InsuranceAgencies;

use App\Http\Resources\InsuranceAgencyResource;
use App\Http\Resources\TreeResource;
use Illuminate\Http\Request;

class TreeInsuranceAgenciesResource extends TreeResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $attributes = InsuranceAgencyResource::make($this->resource)->resolve($request);
        unset($attributes['id']);

        return $this->treeData($request, $this->title, $attributes);
    }
}
