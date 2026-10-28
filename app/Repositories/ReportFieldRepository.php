<?php

namespace App\Repositories;

use App\Interfaces\ReportFieldRepositoryInterface;
use App\Models\ReportField;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportFieldRepository implements ReportFieldRepositoryInterface
{
    public function search(array $filters): Collection|LengthAwarePaginator
    {
        return ReportField::searchRecords(
            ['order_field' => 'sort_order', 'order_type' => 'ASC', ...$filters],
            fn(Builder $query): Builder => $query->withCount('reports'),
        );
    }

    public function active(): Collection
    {
        return ReportField::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function findOrFail(int $id): ReportField
    {
        return ReportField::query()->withCount('reports')->findOrFail($id);
    }

    public function create(array $data): ReportField
    {
        return ReportField::query()->create($data)->loadCount('reports');
    }

    public function update(ReportField $field, array $data): ReportField
    {
        $field->update($data);

        return $field->refresh()->loadCount('reports');
    }

    public function delete(ReportField $field): void
    {
        $field->delete();
    }

    public function nextSortOrder(): int
    {
        return (int)ReportField::query()->max('sort_order') + 1;
    }
}
