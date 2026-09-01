<?php

namespace App\Repositories\User;

use App\Interfaces\UserInterface;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserInterface
{
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
        return $this->buildTree($this->usersForTree($params));
    }

    public function treeForCompany(int $companyId, array $params): Collection
    {
        return $this->buildTree($this->usersForTree(
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

    /**
     * @param  Collection<int, User>  $users
     * @return Collection<int, User>
     */
    private function buildTree(Collection $users): Collection
    {
        $usersById = $users->keyBy(fn (User $user): int => (int) $user->id);
        $roots = new Collection;

        $users->each(fn (User $user) => $user->setRelation('children', new Collection));

        foreach ($users as $user) {
            $parent = $user->parent_id === null
                ? null
                : $usersById->get((int) $user->parent_id);

            if ($parent instanceof User && (int) $parent->id !== (int) $user->id) {
                $parent->children->push($user);

                continue;
            }

            $roots->push($user);
        }

        return $roots->values();
    }
}
