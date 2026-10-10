<?php

namespace App\Services\Company\TransportContract;

use App\Enums\TransportContractItemType;
use App\Helpers\ServiceResult;
use App\Interfaces\Company\TransportContractRepositoryInterface;
use App\Interfaces\UserInterface;
use App\Models\User;
use App\Services\Company\Waybill\IssuedWaybillDeletionGuard;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransportContractService
{
    public function __construct(
        protected TransportContractRepositoryInterface $transportContractRepository,
        protected IssuedWaybillDeletionGuard $issuedWaybillDeletionGuard,
        protected TransportContractAccessService $transportContractAccessService,
        protected UserInterface $userRepository,
    ) {}

    public function index(int $companyId, User $user, array $filters): ServiceResult
    {
        return ServiceResult::success($this->transportContractRepository->search(
            $companyId,
            $filters,
            $user->id,
            $this->transportContractAccessService->canViewAll($user),
        ));
    }

    public function store(int $companyId, array $data): ServiceResult
    {
        return DB::transaction(function () use ($companyId, $data): ServiceResult {
            $items = Arr::pull($data, 'items');
            $this->clearDefaultWhenSelected($companyId, $data);

            return ServiceResult::success(
                $this->transportContractRepository->createWithItems($companyId, $data, $items),
            );
        });
    }

    public function show(int $companyId, int $id, User $user): ServiceResult
    {
        $transportContract = $this->transportContractRepository->findAccessibleOrFail(
            $companyId,
            $id,
            $user->id,
            $this->transportContractAccessService->canViewAll($user),
        );

        return ServiceResult::success($transportContract->load('users:id'));
    }

    public function update(int $companyId, int $id, User $user, array $data): ServiceResult
    {
        return DB::transaction(function () use ($companyId, $id, $user, $data): ServiceResult {
            $transportContract = $this->transportContractRepository->findAccessibleOrFail(
                $companyId,
                $id,
                $user->id,
                $this->transportContractAccessService->canViewAll($user),
            );
            $items = Arr::pull($data, 'items');
            $this->clearDefaultWhenSelected($companyId, $data, $transportContract->getKey());
            $transportContract = $this->transportContractRepository->update($transportContract, $data);

            if ($items !== null) {
                $transportContract = $this->transportContractRepository->syncItems($transportContract, $items);
            }

            return ServiceResult::success($transportContract);
        });
    }

    public function destroy(int $companyId, int $id, User $user): ServiceResult
    {
        $transportContract = $this->transportContractRepository->findAccessibleOrFail(
            $companyId,
            $id,
            $user->id,
            $this->transportContractAccessService->canViewAll($user),
        );
        $this->issuedWaybillDeletionGuard->ensureReferenceCanBeDeleted(
            $companyId,
            ['transport_contract_id'],
            $id,
            'قرارداد حمل',
        );
        $this->transportContractRepository->delete($transportContract);

        return ServiceResult::success(__('public.delete_success', ['attribute' => 'قرارداد حمل']));
    }

    /** @param list<int> $userIds */
    public function syncUsers(int $companyId, int $id, array $userIds): ServiceResult
    {
        return DB::transaction(function () use ($companyId, $id, $userIds): ServiceResult {
            $transportContract = $this->transportContractRepository->findOrFail($companyId, $id);
            $eligibleUserIds = $this->userRepository->eligibleTransportContractUserIds(
                $companyId,
                $userIds,
            );

            if (count($eligibleUserIds) !== count($userIds)) {
                throw ValidationException::withMessages([
                    'user_ids' => __('public.transport_contract_users_invalid'),
                ]);
            }

            $assignedUserIds = $this->transportContractRepository->syncUsers(
                $transportContract,
                $eligibleUserIds,
            );

            return ServiceResult::success([
                'transport_contract_id' => $transportContract->id,
                'user_ids' => $assignedUserIds,
                'is_public' => $assignedUserIds === [],
            ]);
        });
    }

    public function users(int $companyId, int $id, User $user): ServiceResult
    {
        $transportContract = $this->transportContractRepository->findAccessibleOrFail(
            $companyId,
            $id,
            $user->id,
            $this->transportContractAccessService->canViewAll($user),
        );

        return ServiceResult::success($this->transportContractRepository->users($transportContract));
    }

    /** @param array<string, mixed> $data */
    private function clearDefaultWhenSelected(
        int $companyId,
        array $data,
        ?int $exceptTransportContractId = null,
    ): void {
        $selectedDefaultFields = array_values(array_filter([
            ($data['is_default'] ?? false) ? 'is_default' : null,
            ...array_map(
                fn (TransportContractItemType $type): ?string => ($data[$type->defaultField()] ?? false)
                    ? $type->defaultField()
                    : null,
                TransportContractItemType::cases(),
            ),
        ]));

        if ($selectedDefaultFields !== []) {
            $this->transportContractRepository->lockCompanyForUpdate($companyId);
            $this->transportContractRepository->clearDefaults(
                $companyId,
                $selectedDefaultFields,
                $exceptTransportContractId,
            );
        }
    }
}
