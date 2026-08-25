<?php

namespace App\Interfaces;

use App\Models\User;

interface UserInterface
{
    public function all(array $params);

    public function allForCompany(int $companyId, array $params);

    public function store(array $data): ?User;

    public function update(array $data, User $user): ?User;

    public function destroy(User $user);

    public function findByUsername(string $username): ?User;

    public function findForCompany(int $companyId, int $userId): User;
}
