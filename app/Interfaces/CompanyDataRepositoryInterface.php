<?php

namespace App\Interfaces;

use App\Models\DynamicModel;
use Illuminate\Database\Eloquent\Builder;

interface CompanyDataRepositoryInterface
{
    public function table(int $companyId, string $table): string;

    public function query(int $companyId, string $tableKey, ?string $alias = null): Builder;

    public function queryWithJoin(int $companyId, string $tableKey, array $joins = []): Builder;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $companyId, string $tableKey, array $data): DynamicModel;
}
