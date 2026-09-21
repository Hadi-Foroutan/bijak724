<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Company\CompanyTableService;
use Illuminate\Console\Command;

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
    protected $description = 'Create missing configured dynamic tables for companies';

    public function __construct(
        protected CompanyTableService $companyTableService,
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

        $companies->each(function (Company $company): void {
            $this->companyTableService->sync($company->id);

            $this->line("Synced dynamic tables for company [{$company->id}].");
        });

        $this->info("Dynamic tables synced for {$companies->count()} companies.");

        return self::SUCCESS;
    }
}
