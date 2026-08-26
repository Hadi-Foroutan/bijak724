<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\Company\CompanyContextService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

abstract class Controller
{
    protected function company(Request $request): Company
    {
        return app(CompanyContextService::class)->company($request);
    }

    protected function companyId(Request $request): int
    {
        return app(CompanyContextService::class)->companyId($request);
    }

    /**
     * @param  Collection<int, Model>|LengthAwarePaginator  $records
     * @param  class-string<JsonResource>  $resourceClass
     */
    protected function resourceCollection(
        Collection|LengthAwarePaginator $records,
        string $resourceClass,
        Request $request,
    ): Collection|LengthAwarePaginator {
        $transform = fn (Model $model): array => $resourceClass::make($model)->resolve($request);

        return $records instanceof LengthAwarePaginator
            ? $records->through($transform)
            : $records->map($transform);
    }
}
