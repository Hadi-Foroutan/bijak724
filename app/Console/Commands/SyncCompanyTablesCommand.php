<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Company\CompanyDataOwnerResolver;
use App\Services\Company\CompanyTableRegistry;
use App\Services\Company\CompanyTableService;
use Illuminate\Console\Command;
use Throwable;

class SyncCompanyTablesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'company-tables:sync
                            {--company= : Sync only the specified company ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create missing company tables, sync existing ones (add, alter and drop columns) and drop tables removed from config/company_tables.php';

    public function __construct(
        protected CompanyTableService $companyTableService,
        protected CompanyTableRegistry $tableRegistry,
        protected CompanyDataOwnerResolver $companyDataOwnerResolver,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $companyId = $this->option('company');
        $companies = Company::query()
            ->when($companyId !== null, fn ($query) => $query->whereKey($companyId))
            ->get();

        if ($companyId !== null && $companies->isEmpty()) {
            $this->error("Company [{$companyId}] not found.");

            return self::FAILURE;
        }

        // Branches share their data owner's tables, so each owner is synced once.
        $ownerCompanyIds = $companies
            ->map(fn (Company $company): int => $this->companyDataOwnerResolver->resolveId($company->id))
            ->unique()
            ->values();
        $failures = 0;

        foreach ($ownerCompanyIds as $ownerCompanyId) {
            $failures += $this->syncCompany($ownerCompanyId);
        }

        if ($failures > 0) {
            $this->error("Dynamic tables synced for {$ownerCompanyIds->count()} companies with {$failures} failed table(s).");

            return self::FAILURE;
        }

        $this->info("Dynamic tables synced for {$ownerCompanyIds->count()} companies.");

        return self::SUCCESS;
    }

    /**
     * Sync every configured table of the company, continuing past tables that fail.
     *
     * @return int the number of failed tables
     */
    private function syncCompany(int $companyId): int
    {
        $failures = 0;

        foreach ($this->tableRegistry->tableKeys() as $tableKey) {
            try {
                $this->companyTableService->syncTable($companyId, $tableKey);
            } catch (Throwable $exception) {
                $failures++;
                $this->error("Company [{$companyId}] table [{$tableKey}] failed: {$exception->getMessage()}");
            }
        }

        try {
            foreach ($this->companyTableService->dropRemovedTables($companyId) as $table) {
                $this->warn("Dropped table [{$table}] (not in config/company_tables.php).");
            }
        } catch (Throwable $exception) {
            $failures++;
            $this->error("Company [{$companyId}] dropping removed tables failed: {$exception->getMessage()}");
        }

        $this->line("Synced dynamic tables for company [{$companyId}].");

        return $failures;
    }
}
