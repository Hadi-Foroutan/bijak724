<?php

namespace App\Console\Commands;

use App\Services\Admin\InsuranceAgencyService;
use Illuminate\Console\Command;

class GenerateInsuranceAgenciesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate-insurance-agencies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the missing country agent ("شرکت {insurer}") of every insurer and, under it, one province agent ("سرپرستی استان {state}") per state; the rest is filled in later';

    public function __construct(protected InsuranceAgencyService $agencyService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $created = $this->agencyService->generateLevels()->data;

        $this->info("Country agents created: {$created['countries']}, province agents created: {$created['provinces']}.");

        return self::SUCCESS;
    }
}
