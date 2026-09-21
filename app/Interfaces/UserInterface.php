<?php

namespace App\Interfaces;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserInterface
{
    public function all(array $params);

    public function allForCompany(int $companyId, array $params);

    /** @return Collection<int, User> */
    public function tree(array $params): Collection;

    /** @return Collection<int, User> */
    public function treeForCompany(int $companyId, array $params): Collection;

    public function store(array $data): ?User;

    public function update(array $data, User $user): ?User;

    public function destroy(User $user);

    public function findByUsername(string $username): ?User;

    public function findForCompany(int $companyId, int $userId): User;

    public function findVisibleForCompany(int $companyId, int $userId): User;
}
