<?php

namespace App\Repositories\Company;

use App\Enums\CompanySettingKey;
use App\Interfaces\Company\CompanySettingRepositoryInterface;
use App\Models\CompanySetting;
use Illuminate\Database\Eloquent\Collection;

class CompanySettingRepository implements CompanySettingRepositoryInterface
{
    public function __construct(protected CompanySetting $companySetting) {}

    public function all(int $companyId): Collection
    {
        return $this->companySetting->newQuery()
            ->where('company_id', $companyId)
            ->get();
    }

    public function ensureDefault(int $companyId, CompanySettingKey $key): CompanySetting
    {
        return $this->companySetting->newQuery()->firstOrCreate(
            [
                'company_id' => $companyId,
                'key' => $key->value,
            ],
            [
                'group_name' => $key->group(),
                'value_type' => $key->valueType()->value,
                'value' => $key->valueType()->serialize($key->defaultValue()),
            ],
        );
    }

    public function upsert(
        int $companyId,
        CompanySettingKey $key,
        bool|int|float|string|null $value,
        int $updatedBy,
    ): CompanySetting {
        return $this->companySetting->newQuery()->updateOrCreate(
            [
                'company_id' => $companyId,
                'key' => $key->value,
            ],
            [
                'group_name' => $key->group(),
                'value_type' => $key->valueType()->value,
                'value' => $key->valueType()->serialize($value),
                'last_updated_by' => $updatedBy,
            ],
        );
    }
}
