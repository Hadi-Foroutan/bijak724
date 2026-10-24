<?php

namespace App\Services\Company\SharedAdditionalField;

use App\Helpers\ServiceResult;
use App\Interfaces\Company\SharedAdditionalFieldRepositoryInterface;
use App\Models\SharedAdditionalField;
use Illuminate\Support\Facades\DB;

class SharedAdditionalFieldService
{
    public function __construct(protected SharedAdditionalFieldRepositoryInterface $sharedAdditionalFieldRepository)
    {
    }

    public function index(int $companyId): ServiceResult
    {
        return ServiceResult::success($this->sharedAdditionalFieldRepository->all($companyId));
    }

    public function show(int $companyId, int $id): ServiceResult
    {
        return ServiceResult::success($this->sharedAdditionalFieldRepository->findOrFail($companyId, $id));
    }

    public function update(int $companyId, int $id, array $data): ServiceResult
    {
        return DB::transaction(function () use ($companyId, $id, $data): ServiceResult {
            $field = $this->sharedAdditionalFieldRepository->findOrFail($companyId, $id);

            return ServiceResult::success($this->sharedAdditionalFieldRepository->update($field, $data));
        });
    }

    public function ensureDefaultsForCompany(int $companyId): void
    {
        foreach (SharedAdditionalField::numbers() as $number) {
            $this->sharedAdditionalFieldRepository->ensureDefault($companyId, $number);
        }
    }
}
