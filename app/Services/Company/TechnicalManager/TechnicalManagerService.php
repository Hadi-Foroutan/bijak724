<?php

namespace App\Services\Company\TechnicalManager;

use App\Enums\StatusEnum;
use App\Helpers\ServiceResult;
use App\Interfaces\Company\TechnicalManagerRepositoryInterface;
use App\Models\TechnicalManager;
use App\Services\Company\Waybill\IssuedWaybillDeletionGuard;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TechnicalManagerService
{
    public function __construct(
        protected TechnicalManagerRepositoryInterface $technicalManagerRepository,
        protected IssuedWaybillDeletionGuard          $issuedWaybillDeletionGuard,
    )
    {
    }

    public function index(int $companyId, array $filters): ServiceResult
    {
        $records = $this->technicalManagerRepository->search($companyId, $filters);

        $this->attachIssuedToday($companyId, $records instanceof LengthAwarePaginator
            ? $records->getCollection()
            : $records);

        return ServiceResult::success($records);
    }

    public function show(int $companyId, int $id): ServiceResult
    {
        return ServiceResult::success($this->withIssuedToday(
            $companyId,
            $this->technicalManagerRepository->findOrFail($companyId, $id),
        ));
    }

    /** @param array<string, mixed> $data */
    public function store(int $companyId, array $data): ServiceResult
    {
        return ServiceResult::success($this->withIssuedToday(
            $companyId,
            $this->technicalManagerRepository->create($companyId, $data),
        ));
    }

    /** @param array<string, mixed> $data */
    public function update(int $companyId, int $id, array $data): ServiceResult
    {
        $technicalManager = $this->technicalManagerRepository->findOrFail($companyId, $id);

        return ServiceResult::success($this->withIssuedToday(
            $companyId,
            $this->technicalManagerRepository->update($technicalManager, $data),
        ));
    }

    public function destroy(int $companyId, int $id): ServiceResult
    {
        $this->issuedWaybillDeletionGuard->ensureReferenceCanBeDeleted(
            $companyId,
            ['technical_manager_id'],
            $id,
            'مدیر فنی',
        );

        $this->technicalManagerRepository->delete($this->technicalManagerRepository->findOrFail($companyId, $id));

        return ServiceResult::success(__('public.delete_success', ['attribute' => 'مدیر فنی']));
    }

    /**
     * Active technical managers with their remaining quota today, for the waybill form.
     *
     * @return Collection<int, TechnicalManager>
     */
    public function activeOptions(int $companyId): Collection
    {
        $managers = $this->technicalManagerRepository
            ->search($companyId, ['status' => StatusEnum::ACTIVE->value, 'order_field' => 'full_name', 'order_type' => 'ASC'])
            ->where('status', StatusEnum::ACTIVE);

        $this->attachIssuedToday($companyId, $managers);

        return $managers->values();
    }

    /**
     * Guard used while issuing a waybill: the manager must be active and still have quota on the issue day.
     * It locks the manager row, so two concurrent issuances cannot both take the last slot.
     */
    public function ensureCanIssue(int $companyId, int $technicalManagerId, CarbonInterface $issuedAt): TechnicalManager
    {
        $technicalManager = $this->technicalManagerRepository->findOrFailForUpdate($companyId, $technicalManagerId);

        if ($technicalManager->status !== StatusEnum::ACTIVE) {
            throw ValidationException::withMessages([
                'technical_manager_id' => __('public.technical_manager_inactive'),
            ]);
        }

        $issuedCount = $this->technicalManagerRepository
            ->issuedCountsForDay($companyId, [$technicalManagerId], $issuedAt)[$technicalManagerId] ?? 0;

        if ($issuedCount >= (int)$technicalManager->daily_limit) {
            throw ValidationException::withMessages([
                'technical_manager_id' => __('public.technical_manager_daily_limit_reached'),
            ]);
        }

        return $technicalManager;
    }

    private function withIssuedToday(int $companyId, TechnicalManager $technicalManager): TechnicalManager
    {
        $this->attachIssuedToday($companyId, collect([$technicalManager]));

        return $technicalManager;
    }

    /** @param Collection<int, TechnicalManager> $managers */
    private function attachIssuedToday(int $companyId, Collection $managers): void
    {
        $counts = $this->technicalManagerRepository->issuedCountsForDay(
            $companyId,
            $managers->pluck('id')->map(fn($id): int => (int)$id)->all(),
            now(),
        );

        $managers->each(fn(TechnicalManager $manager) => $manager->setAttribute(
            'issued_today',
            $counts[$manager->getKey()] ?? 0,
        ));
    }
}
