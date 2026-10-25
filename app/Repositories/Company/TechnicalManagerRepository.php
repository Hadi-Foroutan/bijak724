<?php

namespace App\Repositories\Company;

use App\Enums\WaybillStatus;
use App\Interfaces\Company\TechnicalManagerRepositoryInterface;
use App\Models\Company\Waybill;
use App\Models\TechnicalManager;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TechnicalManagerRepository implements TechnicalManagerRepositoryInterface
{
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        return TechnicalManager::searchRecords(
            $filters,
            fn($query) => $query->where('company_id', $companyId),
        );
    }

    public function findOrFail(int $companyId, int $id): TechnicalManager
    {
        return TechnicalManager::query()->where('company_id', $companyId)->findOrFail($id);
    }

    public function findOrFailForUpdate(int $companyId, int $id): TechnicalManager
    {
        return TechnicalManager::query()
            ->where('company_id', $companyId)
            ->lockForUpdate()
            ->findOrFail($id);
    }

    public function create(int $companyId, array $data): TechnicalManager
    {
        return TechnicalManager::query()->create([...$data, 'company_id' => $companyId]);
    }

    public function update(TechnicalManager $technicalManager, array $data): TechnicalManager
    {
        unset($data['company_id']);
        $technicalManager->update($data);

        return $technicalManager->refresh();
    }

    public function delete(TechnicalManager $technicalManager): void
    {
        $technicalManager->delete();
    }

    public function issuedCountsForDay(int $companyId, array $technicalManagerIds, CarbonInterface $day): array
    {
        if ($technicalManagerIds === []) {
            return [];
        }

        // ابطال‌شده‌ها هم سهمیهٔ همان روز را مصرف کرده‌اند.
        return Waybill::queryForCompany($companyId)
            ->whereIn('technical_manager_id', $technicalManagerIds)
            ->whereIn('status', [WaybillStatus::Completed->value, WaybillStatus::Canceled->value])
            ->whereBetween('issued_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->selectRaw('technical_manager_id, COUNT(*) as issued_count')
            ->groupBy('technical_manager_id')
            ->pluck('issued_count', 'technical_manager_id')
            ->map(fn($count): int => (int)$count)
            ->all();
    }
}
