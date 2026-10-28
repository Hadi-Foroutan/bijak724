<?php

namespace App\Repositories;

use App\Interfaces\GlobalSettingRepositoryInterface;
use App\Models\Setting;

class GlobalSettingRepository implements GlobalSettingRepositoryInterface
{
    public function value(string $key): ?string
    {
        return Setting::query()->where('key', $key)->value('value');
    }

    public function upsert(string $key, ?string $value, int $updatedBy): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'last_updated_by' => $updatedBy],
        );
    }
}
