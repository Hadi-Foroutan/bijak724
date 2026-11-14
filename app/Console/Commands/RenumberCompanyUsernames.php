<?php

namespace App\Console\Commands;

use App\Enums\RoleEnum;
use App\Models\Company;
use App\Models\User;
use App\Services\Auth\AccessTokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RenumberCompanyUsernames extends Command
{
    protected $signature = 'users:renumber-usernames
        {--company= : Only this company id}
        {--dry-run : Show the changes without saving them}
        {--force : Do not ask for confirmation}';

    protected $description = 'Give every company user the generated username (<panel_code><1000, 1001, ...>), the company manager first';

    public function handle(AccessTokenService $tokens): int
    {
        $companies = Company::query()
            ->when($this->option('company'), fn($query, $id) => $query->whereKey((int)$id))
            ->orderBy('id')
            ->get();

        $plans = $companies->mapWithKeys(fn(Company $company): array => [$company->id => $this->plan($company)]);
        $changes = $plans->flatten(1)->count();

        foreach ($plans as $companyId => $rows) {
            foreach ($rows as $row) {
                $this->line("company {$companyId}: user {$row['id']}  {$row['from']} -> {$row['to']}");
            }
        }

        $this->info("{$changes} username(s) to change in {$plans->filter()->count()} company(ies).");

        if ($changes === 0 || $this->option('dry-run')) {
            return self::SUCCESS;
        }

        $this->warn('The affected users are logged out: their tokens are expired and they must log in with the new username.');

        if (!$this->option('force') && !$this->confirm('Apply these changes?')) {
            return self::FAILURE;
        }

        foreach ($companies as $company) {
            $this->apply($company, $tokens);
        }

        $this->info('Done.');

        return self::SUCCESS;
    }

    /**
     * The usernames the company's users should have: the manager first, then the other users, then the deleted ones
     * (they keep their number so it is never issued again). Only the users that differ are returned.
     *
     * @return Collection<int, array{id: int, from: string, to: string}>
     */
    private function plan(Company $company): Collection
    {
        $managerIds = User::query()
            ->where('company_id', $company->id)
            ->whereHas('roles', fn($query) => $query->where('name', RoleEnum::COMPANY_MANAGER->value))
            ->pluck('id')
            ->flip();

        $users = User::withTrashed()
            ->where('company_id', $company->id)
            ->get()
            ->sortBy(fn(User $user): array => [
                $user->trashed() ? 2 : ($managerIds->has($user->id) ? 0 : 1),
                $user->id,
            ])
            ->values();

        return $users
            ->map(fn(User $user, int $index): array => [
                'id' => $user->id,
                'from' => $user->username,
                'to' => $company->panel_code . (1000 + $index),
            ])
            ->filter(fn(array $row): bool => $row['from'] !== $row['to'])
            ->values();
    }

    private function apply(Company $company, AccessTokenService $tokens): void
    {
        $rows = $this->plan($company);
        $total = User::withTrashed()->where('company_id', $company->id)->count();

        DB::transaction(function () use ($company, $rows, $total, $tokens): void {
            // Park the users on temporary names first, so a new username never clashes with an old one still in use.
            foreach ($rows as $row) {
                User::withTrashed()->whereKey($row['id'])->update(['username' => "renumber-{$row['id']}"]);
            }

            foreach ($rows as $row) {
                $taken = User::withTrashed()->where('username', $row['to'])->exists();

                if ($taken) {
                    throw new RuntimeException("Username {$row['to']} already belongs to a user outside company {$company->id}.");
                }

                User::withTrashed()->whereKey($row['id'])->update(['username' => $row['to']]);
            }

            $company->forceFill(['last_user_sequence' => 999 + $total])->save();

            foreach ($rows as $row) {
                $tokens->expireAll(User::withTrashed()->findOrFail($row['id']));
            }
        });

        $this->line("company {$company->id}: {$rows->count()} updated.");
    }
}
