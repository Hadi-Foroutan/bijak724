<?php

namespace App\Services\Company\TransportContract;

use App\Enums\TransportContractItemType;
use App\Helpers\ServiceResult;
use App\Interfaces\Company\TransportContractRepositoryInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class TransportContractService
{
    public function __construct(
        protected TransportContractRepositoryInterface $transportContractRepository,
    ) {}

    public function index(int $companyId, array $filters): ServiceResult
    {
        return ServiceResult::success($this->transportContractRepository->search($companyId, $filters));
    }

    public function store(int $companyId, array $data): ServiceResult
    {
        return DB::transaction(function () use ($companyId, $data): ServiceResult {
            $items = Arr::pull($data, 'items');
            $this->clearDefaultWhenSelected($companyId, $data);
            $transportContract = $this->transportContractRepository->create($companyId, $data);
            $transportContract->items()->createMany($items);

            return ServiceResult::success($transportContract->load('items'));
        });
    }

    public function show(int $companyId, int $id): ServiceResult
    {
        return ServiceResult::success($this->transportContractRepository->findOrFail($companyId, $id));
    }

    public function update(int $companyId, int $id, array $data): ServiceResult
    {
        return DB::transaction(function () use ($companyId, $id, $data): ServiceResult {
            $transportContract = $this->transportContractRepository->findOrFail($companyId, $id);
            $items = Arr::pull($data, 'items');
            $this->clearDefaultWhenSelected($companyId, $data);
            $transportContract = $this->transportContractRepository->update($transportContract, $data);

            if ($items !== null) {
                $transportContract->items()->delete();
                $transportContract->items()->createMany($items);
            }

            return ServiceResult::success($transportContract->load('items'));
        });
    }

    public function destroy(int $companyId, int $id): ServiceResult
    {
        $transportContract = $this->transportContractRepository->findOrFail($companyId, $id);
        $this->transportContractRepository->delete($transportContract);

        return ServiceResult::success(__('public.delete_success', ['attribute' => 'قرارداد حمل']));
    }

    /** @param array<string, mixed> $data */
    private function clearDefaultWhenSelected(int $companyId, array $data): void
    {
        $selectedDefaultFields = array_values(array_filter(
            array_map(
                fn (TransportContractItemType $type): ?string => ($data[$type->defaultField()] ?? false)
                    ? $type->defaultField()
                    : null,
                TransportContractItemType::cases(),
            ),
        ));

        if ($selectedDefaultFields !== []) {
            $this->transportContractRepository->clearDefaults($companyId, $selectedDefaultFields);
        }
    }
}
