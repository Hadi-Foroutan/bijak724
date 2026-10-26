<?php

namespace App\Services\Setting;

use App\Enums\GlobalSettingKey;
use App\Helpers\ServiceResult;
use App\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class GlobalSettingService
{
    public function value(GlobalSettingKey $key): bool|int|float|string|null
    {
        $stored = Setting::query()->where('key', $key->value)->value('value');

        return $stored === null ? $key->defaultValue() : $key->valueType()->cast($stored);
    }

    public function show(): ServiceResult
    {
        $result = [];

        foreach (GlobalSettingKey::cases() as $key) {
            data_set($result, $key->value, $this->value($key));
        }

        return ServiceResult::success($result);
    }

    /** @param array<string, mixed> $data */
    public function update(int $updatedBy, array $data): ServiceResult
    {
        DB::transaction(function () use ($updatedBy, $data): void {
            foreach (GlobalSettingKey::cases() as $key) {
                if (!Arr::has($data, $key->value)) {
                    continue;
                }

                Setting::query()->updateOrCreate(
                    ['key' => $key->value],
                    [
                        'value' => $key->valueType()->serialize(data_get($data, $key->value)),
                        'last_updated_by' => $updatedBy,
                    ],
                );
            }
        });

        return $this->show();
    }
}
