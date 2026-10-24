<?php

namespace App\Interfaces\Company;

use App\Models\SharedAdditionalField;
use Illuminate\Database\Eloquent\Collection;

interface SharedAdditionalFieldRepositoryInterface
{
    /** @return Collection<int, SharedAdditionalField> */
    public function all(int $companyId): Collection;

    public function findOrFail(int $companyId, int $id): SharedAdditionalField;

    public function update(SharedAdditionalField $field, array $data): SharedAdditionalField;

    public function ensureDefault(int $companyId, int $number): SharedAdditionalField;
}
