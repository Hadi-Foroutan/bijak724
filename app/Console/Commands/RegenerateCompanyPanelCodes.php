<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use App\Services\Auth\AccessTokenService;
use App\Services\Company\PanelCodeGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RegenerateCompanyPanelCodes extends Command
{
    protected $signature = 'companies:regenerate-panel-codes
        {--company= : Only this company id}
        {--invalid-only : Only the companies whose panel code is not a valid code of the current length}
        {--dry-run : Show the changes without saving them}
        {--force : Do not ask for confirmation}';

    protected $description = 'Give the companies a new panel code, and move their generated usernames (<panel_code><sequence>) to it';

    public function handle(PanelCodeGenerator $generator, AccessTokenService $tokens): int
    {
        $companies = Company::withTrashed()
            ->when($this->option('company'), fn($query, $id) => $query->whereKey((int)$id))
            ->orderBy('id')
            ->get()
            ->when($this->option('invalid-only'), fn($all) => $all->reject(
                fn(Company $company): bool => $generator->isValid((string)$company->panel_code),
            ));

        $this->info("{$companies->count()} company(ies) will get a new panel code of " . PanelCodeGenerator::LENGTH . ' digits.');

        if ($companies->isEmpty() || $this->option('dry-run')) {
            $companies->each(fn(Company $company) => $this->line("company {$company->id}: {$company->panel_code} -> (new)"));

            return self::SUCCESS;
        }

        $this->warn('Their users\' usernames change with it and their tokens are expired: they must log in again.');

        if (!$this->option('force') && !$this->confirm('Apply these changes?')) {
            return self::FAILURE;
        }

        foreach ($companies as $company) {
            DB::transaction(function () use ($company, $generator, $tokens): void {
                $old = (string)$company->panel_code;
                $new = $generator->generate();
                $users = User::withTrashed()->where('company_id', $company->id)->get();

                $company->forceFill(['panel_code' => $new])->save();

                foreach ($users as $user) {
                    $sequence = substr((string)$user->username, strlen($old));

                    // Only the generated usernames follow the panel code; a username chosen by an admin stays.
                    if (!str_starts_with((string)$user->username, $old) || !ctype_digit($sequence)) {
                        continue;
                    }

                    User::withTrashed()->whereKey($user->id)->update(['username' => $new . $sequence]);
                    $tokens->expireAll($user);
                }

                $this->line("company {$company->id}: {$old} -> {$new}");
            });
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
