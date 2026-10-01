<?php

namespace App\Services\Company\Settings;

use App\Enums\CompanySettingKey;
use App\Enums\SettingValueType;
use App\Helpers\ServiceResult;
use App\Interfaces\Company\CompanySettingRepositoryInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use LogicException;

class CompanySettingService
{
    /** @var array<int, array<string, bool|int|float|string|null>> */
    private array $cachedValues = [];

    public function __construct(
        protected CompanySettingRepositoryInterface $companySettingRepository,
    ) {}

    public function show(int $companyId): ServiceResult
    {
        $result = [];

        foreach (CompanySettingKey::cases() as $key) {
            data_set($result, $key->value, $this->value($companyId, $key));
        }

        return ServiceResult::success($result);
    }

    /** @param array<string, mixed> $data */
    public function update(int $companyId, int $updatedBy, array $data): ServiceResult
    {
        DB::transaction(function () use ($companyId, $updatedBy, $data): void {
            foreach (CompanySettingKey::cases() as $key) {
                if (! Arr::has($data, $key->value)) {
                    continue;
                }

                $this->companySettingRepository->upsert(
                    $companyId,
                    $key,
                    data_get($data, $key->value),
                    $updatedBy,
                );
            }
        });

        unset($this->cachedValues[$companyId]);

        return $this->show($companyId);
    }

    public function ensureDefaultsForCompany(int $companyId): void
    {
        foreach (CompanySettingKey::cases() as $key) {
            $this->companySettingRepository->ensureDefault($companyId, $key);
        }

        unset($this->cachedValues[$companyId]);
    }

    public function boolean(int $companyId, CompanySettingKey $key): bool
    {
        $this->ensureType($key, SettingValueType::Boolean);

        return (bool) $this->value($companyId, $key);
    }

    public function integer(int $companyId, CompanySettingKey $key): int
    {
        $this->ensureType($key, SettingValueType::Integer);

        return (int) $this->value($companyId, $key);
    }

    public function decimal(int $companyId, CompanySettingKey $key): float
    {
        $this->ensureType($key, SettingValueType::Decimal);

        return (float) $this->value($companyId, $key);
    }

    public function string(int $companyId, CompanySettingKey $key): string
    {
        $this->ensureType($key, SettingValueType::String);

        return (string) $this->value($companyId, $key);
    }

    public function value(int $companyId, CompanySettingKey $key): bool|int|float|string|null
    {
        $values = $this->valuesForCompany($companyId);

        return $values[$key->value];
    }

    /** @return array<string, bool|int|float|string|null> */
    private function valuesForCompany(int $companyId): array
    {
        if (isset($this->cachedValues[$companyId])) {
            return $this->cachedValues[$companyId];
        }

        $storedSettings = $this->companySettingRepository->all($companyId)->keyBy('key');
        $values = [];

        foreach (CompanySettingKey::cases() as $key) {
            $setting = $storedSettings->get($key->value);
            $values[$key->value] = $setting === null
                ? $key->defaultValue()
                : $key->valueType()->cast($setting->value);
        }

        return $this->cachedValues[$companyId] = $values;
    }

    private function ensureType(CompanySettingKey $key, SettingValueType $expectedType): void
    {
        if ($key->valueType() !== $expectedType) {
            throw new LogicException("Setting [{$key->value}] is not {$expectedType->value}.");
        }
    }
}
