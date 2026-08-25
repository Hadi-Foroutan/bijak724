<?php

namespace App\Repositories\User;

use App\Interfaces\UserInterface;
use App\Models\User;

class UserRepository implements UserInterface
{
    public function all(array $params)
    {
        return User::searchRecords(
            $params,
            fn ($query) => $query->with(['roles']),
        );
    }

    public function allForCompany(int $companyId, array $params)
    {
        return User::searchRecords(
            $params,
            fn ($query) => $query
                ->where('company_id', $companyId)
                ->with(['roles']),
        );
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
            ->with('roles')
            ->findOrFail($userId);
    }
}
