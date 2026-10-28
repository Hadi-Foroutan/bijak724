<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\ReportRepositoryInterface;
use App\Models\ReportDefinition;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportRepository implements ReportRepositoryInterface
{
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator
    {
        return ReportDefinition::searchRecords(
            $filters,
            fn(Builder $query): Builder => $query->where('company_id', $companyId)->with('fields'),
        );
    }

    public function findOrFail(int $companyId, int $id): ReportDefinition
    {
        return ReportDefinition::query()
            ->where('company_id', $companyId)
            ->with('fields')
            ->findOrFail($id);
    }

    public function create(int $companyId, int $userId, string $name): ReportDefinition
    {
        return ReportDefinition::query()->create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'name' => $name,
        ]);
    }

    public function update(ReportDefinition $report, array $data): ReportDefinition
    {
        unset($data['company_id'], $data['user_id']);
        $report->update($data);

        return $report->refresh()->load('fields');
    }

    public function delete(ReportDefinition $report): void
    {
        $report->delete();
    }

    public function syncFields(ReportDefinition $report, array $fieldIds, array $options = []): ReportDefinition
    {
        $report->fields()->sync(
            collect(array_values($fieldIds))
                ->mapWithKeys(fn(int $id, int $position): array => [$id => [
                    'sort_order' => $position,
                    'align' => $options[$id]['align'] ?? 'right',
                    'width' => $options[$id]['width'] ?? null,
                ]])
                ->all(),
        );

        return $report->load('fields');
    }
}
