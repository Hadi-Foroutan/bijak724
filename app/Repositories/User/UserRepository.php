<?php

namespace App\Repositories\User;

use App\Interfaces\UserInterface;
use App\Models\User;
use App\Services\TreeBuilder;
use Closure;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserInterface
{
    public function __construct(
        protected TreeBuilder $treeBuilder,
    ) {}

    public function all(array $params)
    {
        return User::searchRecords(
            $params,
            fn ($query) => $query->with(['roles', 'parent']),
        );
    }

    public function allForCompany(int $companyId, array $params)
    {
        return User::searchRecords(
            $params,
            fn ($query) => $query
                ->where('company_id', $companyId)
                ->with(['roles', 'parent']),
        );
    }

    public function tree(array $params): Collection
    {
        return $this->treeBuilder->build($this->usersForTree($params));
    }

    public function treeForCompany(int $companyId, array $params): Collection
    {
        return $this->treeBuilder->build($this->usersForTree(
            $params,
            fn ($query) => $query->where('company_id', $companyId),
        ));
    }

    public function store(array $data): ?User
    {
        return User::create($data);
    }

    public function update(array $data, User $user): ?User
    {
        $user->update($data);

        return $user->fresh();
    }

    public function destroy(User $user): void
    {
        $user->delete();
    }

    public function findByUsername(string $username): ?User
    {
        return User::query()->where('username', $username)->first();
    }

    public function findForCompany(int $companyId, int $userId): User
    {
        return User::query()
            ->where('company_id', $companyId)
            ->with(['roles', 'parent'])
            ->findOrFail($userId);
    }

    private function usersForTree(array $params, ?Closure $queryCallback = null): Collection
    {
        unset($params['tree'], $params['paginate']);

        /** @var Collection<int, User> */
        return User::searchRecords($params, $queryCallback);
    }
}
