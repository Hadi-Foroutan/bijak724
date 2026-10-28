<?php

namespace App\Services\Setting;

use App\Enums\GlobalSettingKey;
use App\Helpers\ServiceResult;
use App\Interfaces\GlobalSettingRepositoryInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class GlobalSettingService
{
    public function __construct(protected GlobalSettingRepositoryInterface $globalSettingRepository)
    {
    }

    public function value(GlobalSettingKey $key): bool|int|float|string|null
    {
        $stored = $this->globalSettingRepository->value($key->value);

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

                $this->globalSettingRepository->upsert(
                    $key->value,
                    $key->valueType()->serialize(data_get($data, $key->value)),
                    $updatedBy,
                );
            }
        });

        return $this->show();
    }
}
