<?php

namespace App\Interfaces\Company;

use App\Models\Company\CanceledReferral;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface CanceledReferralRepositoryInterface
{
    public function query(int $companyId): Builder;

    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator;

    /** @param array<string, mixed> $data */
    public function create(int $companyId, array $data): CanceledReferral;
}
