<?php

namespace App\Interfaces\Company;

use App\Enums\CompanySettingKey;
use App\Models\CompanySetting;
use Illuminate\Database\Eloquent\Collection;

interface CompanySettingRepositoryInterface
{
    /** @return Collection<int, CompanySetting> */
    public function all(int $companyId): Collection;

    public function ensureDefault(int $companyId, CompanySettingKey $key): CompanySetting;

    public function upsert(
        int $companyId,
        CompanySettingKey $key,
        bool|int|float|string|null $value,
        int $updatedBy,
    ): CompanySetting;
}
