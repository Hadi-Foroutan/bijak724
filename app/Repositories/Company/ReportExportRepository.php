<?php

namespace App\Repositories\Company;

use App\Enums\ReportExportStatus;
use App\Interfaces\Company\ReportExportRepositoryInterface;
use App\Models\ReportExport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportExportRepository implements ReportExportRepositoryInterface
{
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        return ReportExport::searchRecords($filters,
            fn(Builder $query): Builder => $query->where('company_id', $companyId),
        );
    }

    public function findOrFail(int $companyId, int $id): ReportExport
    {
        return ReportExport::query()
            ->where('company_id', $companyId)
            ->findOrFail($id);
    }

    public function find(int $id): ?ReportExport
    {
        return ReportExport::query()->with('user')->find($id);
    }

    public function create(array $data): ReportExport
    {
        return ReportExport::query()->create($data);
    }

    public function update(ReportExport $export, array $data): ReportExport
    {
        $export->update($data);

        return $export->refresh();
    }

    public function delete(ReportExport $export): void
    {
        $export->delete();
    }

    public function activeCount(int $companyId, int $userId): int
    {
        return ReportExport::query()
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->whereIn('status', [ReportExportStatus::Queued, ReportExportStatus::Processing])
            ->count();
    }

    public function expiredCompleted(): Collection
    {
        return ReportExport::query()
            ->where('status', ReportExportStatus::Completed)
            ->where('expires_at', '<=', now())
            ->get();
    }
}
