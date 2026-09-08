<?php

namespace App\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface GeneralOptionRepositoryInterface
{
    /** @param array<string, mixed> $filters */
    public function cargos(array $filters): Collection|LengthAwarePaginator;

    /** @param array<string, mixed> $filters */
    public function packaging(array $filters): Collection|LengthAwarePaginator;

    /** @param array<string, mixed> $filters */
    public function fleetTypes(array $filters): Collection|LengthAwarePaginator;

    /** @param array<string, mixed> $filters */
    public function fleetSystems(array $filters): Collection|LengthAwarePaginator;

    /** @param array<string, mixed> $filters */
    public function states(array $filters): Collection|LengthAwarePaginator;

    /** @param array<string, mixed> $filters */
    public function cities(array $filters): Collection|LengthAwarePaginator;

    /** @param array<string, mixed> $filters */
    public function insuranceCompanies(array $filters): Collection|LengthAwarePaginator;
}
