<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\TransportContractRepositoryInterface;
use App\Models\Company;
use App\Models\TransportContract;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class TransportContractRepository implements TransportContractRepositoryInterface
{
    public function lockCompanyForUpdate(int $companyId): void
    {
        Company::query()->whereKey($companyId)->lockForUpdate()->firstOrFail();
    }

    public function search(
        int $companyId,
        array $filters,
        ?int $userId = null,
        bool $canViewAll = true,
    ): Collection|LengthAwarePaginator {
        return TransportContract::searchRecords($filters, function (Builder $query) use (
            $companyId,
            $userId,
            $canViewAll,
        ): void {
            $this->applyVisibility(
                $query->where('company_id', $companyId),
                $userId,
                $canViewAll,
            )->with('items');
        });
    }

    public function findOrFail(int $companyId, int $id): TransportContract
    {
        return TransportContract::query()
            ->where('company_id', $companyId)
            ->with('items')
            ->findOrFail($id);
    }

    public function findAccessibleOrFail(
        int $companyId,
        int $id,
        int $userId,
        bool $canViewAll,
    ): TransportContract {
        return $this->applyVisibility(
            TransportContract::query()->where('company_id', $companyId),
            $userId,
            $canViewAll,
        )
            ->with('items')
            ->findOrFail($id);
    }

    public function options(
        int $companyId,
        ?int $userId = null,
        bool $canViewAll = true,
    ): Collection {
        return $this->applyVisibility(
            TransportContract::query()->where('company_id', $companyId),
            $userId,
            $canViewAll,
        )
            ->with('items')
            ->orderBy('title')
            ->get();
    }

    public function accessibleExists(
        int $companyId,
        int $id,
        int $userId,
        bool $canViewAll,
    ): bool {
        return $this->applyVisibility(
            TransportContract::query()->where('company_id', $companyId),
            $userId,
            $canViewAll,
        )->whereKey($id)->exists();
    }

    public function existsRule(int $companyId): Exists
    {
        return Rule::exists(TransportContract::class, 'id')->where('company_id', $companyId);
    }

    public function create(int $companyId, array $data): TransportContract
    {
        return TransportContract::query()->create([...$data, 'company_id' => $companyId]);
    }

    public function createWithItems(int $companyId, array $data, array $items): TransportContract
    {
        $transportContract = $this->create($companyId, $data);
        $transportContract->items()->createMany($items);

        return $transportContract->load('items');
    }

    public function update(TransportContract $transportContract, array $data): TransportContract
    {
        unset($data['company_id']);
        $transportContract->update($data);

        return $transportContract->refresh();
    }

    public function syncItems(TransportContract $transportContract, array $items): TransportContract
    {
        $transportContract->items()->delete();
        $transportContract->items()->createMany($items);

        return $transportContract->load('items');
    }

    public function clearDefaults(int $companyId, array $fields): void
    {
        foreach ($fields as $field) {
            TransportContract::query()
                ->where('company_id', $companyId)
                ->where($field, true)
                ->update([$field => false]);
        }
    }

    public function syncUsers(TransportContract $transportContract, array $userIds): array
    {
        $transportContract->users()->sync($userIds);
        $transportContract->forceFill(['is_public' => $userIds === []])->save();

        return $transportContract->users()
            ->orderBy('users.id')
            ->pluck('users.id')
            ->map(fn (mixed $userId): int => (int) $userId)
            ->all();
    }

    /** @return EloquentCollection<int, User> */
    public function users(TransportContract $transportContract): EloquentCollection
    {
        return $transportContract->users()
            ->with(['roles', 'parent'])
            ->orderBy('users.id')
            ->get();
    }

    public function delete(TransportContract $transportContract): void
    {
        $transportContract->delete();
    }

    private function applyVisibility(
        Builder $query,
        ?int $userId,
        bool $canViewAll,
    ): Builder {
        if ($canViewAll) {
            return $query;
        }

        return $query->where(function (Builder $visibilityQuery) use ($userId): void {
            $visibilityQuery
                ->where('is_public', true)
                ->orWhereHas(
                    'users',
                    fn (Builder $userQuery): Builder => $userQuery->whereKey($userId ?? 0),
                );
        });
    }
}
