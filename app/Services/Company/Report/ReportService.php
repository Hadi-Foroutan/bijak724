<?php

namespace App\Services\Company\Report;

use App\Helpers\ServiceResult;
use App\Models\ReportDefinition;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Saved report definitions: a name and the waybill fields the user picked. Every user only sees their own. */
class ReportService
{
    public function __construct(
        protected WaybillReportFieldCatalog $catalog,
        protected WaybillReportQuery        $query,
    )
    {
    }

    public function fields(): ServiceResult
    {
        return ServiceResult::success($this->catalog->refresh()->toArray());
    }

    /** @param array<string, mixed> $filters */
    public function index(int $companyId, int $userId, array $filters): ServiceResult
    {
        return ServiceResult::success(ReportDefinition::searchRecords(
            $filters,
            fn(Builder $query): Builder => $query->where('company_id', $companyId)->where('user_id', $userId)->with('fields'),
        ));
    }

    public function show(int $companyId, int $userId, int $id): ServiceResult
    {
        return ServiceResult::success($this->findOrFail($companyId, $userId, $id));
    }

    /** @param array{name: string, field_ids: list<int>} $data */
    public function store(int $companyId, int $userId, array $data): ServiceResult
    {
        $report = DB::transaction(function () use ($companyId, $userId, $data): ReportDefinition {
            $report = ReportDefinition::query()->create([
                'company_id' => $companyId,
                'user_id' => $userId,
                'name' => $data['name'],
            ]);
            $report->syncFields($data['field_ids']);

            return $report;
        });

        return ServiceResult::success($report->load('fields'));
    }

    /** @param array{name?: string, field_ids?: list<int>} $data */
    public function update(int $companyId, int $userId, int $id, array $data): ServiceResult
    {
        $report = $this->findOrFail($companyId, $userId, $id);

        DB::transaction(function () use ($report, $data): void {
            if (isset($data['name'])) {
                $report->update(['name' => $data['name']]);
            }

            if (isset($data['field_ids'])) {
                $report->syncFields($data['field_ids']);
            }
        });

        return ServiceResult::success($report->refresh()->load('fields'));
    }

    public function destroy(int $companyId, int $userId, int $id): ServiceResult
    {
        $this->findOrFail($companyId, $userId, $id)->delete();

        return ServiceResult::success(__('public.delete_success', ['attribute' => 'گزارش']));
    }

    /**
     * The company's waybills with only the report's fields.
     *
     * @param array<string, mixed> $filters
     * @return array{columns: list<array{key: string, label: string}>, records: Collection<int, array<string, mixed>>|LengthAwarePaginator}
     */
    public function waybills(int $companyId, User $user, int $id, array $filters): ServiceResult
    {
        $this->catalog->refresh();
        $report = $this->findOrFail($companyId, (int)$user->getKey(), $id);
        $fields = $report->fieldKeys();
        $query = $this->query->build($companyId, $user, $fields, $filters);

        $records = $query->getModel()->advancedSearchResults(
            $query,
            ['paginate' => true, ...$filters],
        );
        $transform = fn($waybill): array => ['id' => $waybill->getKey(), ...$this->catalog->row($waybill, $fields)];

        return ServiceResult::success([
            'columns' => $this->catalog->columns($fields),
            'records' => $records instanceof LengthAwarePaginator
                ? $records->through($transform)
                : $records->map($transform),
        ]);
    }

    private function findOrFail(int $companyId, int $userId, int $id): ReportDefinition
    {
        return ReportDefinition::query()
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->with('fields')
            ->findOrFail($id);
    }
}
