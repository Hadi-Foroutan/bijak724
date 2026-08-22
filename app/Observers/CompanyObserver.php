<?php

namespace App\Observers;

use App\Models\Company;
use App\Services\Company\CompanyTableService;

class CompanyObserver
{
    public function __construct(
        protected CompanyTableService $companyTableService,
    )
    {
    }

    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        // Generate Company Tables
        $this->companyTableService->createCompanyTables(
             $company->id,
             config('company_tables'),
         );
    }

    /**
     * Handle the Company "updated" event.
     */
    public function updated(Company $company): void
    {
        //
    }

    /**
     * Handle the Company "deleted" event.
     */
    public function deleted(Company $company): void
    {
        //
    }

    /**
     * Handle the Company "restored" event.
     */
    public function restored(Company $company): void
    {
        //
    }

    /**
     * Handle the Company "force deleted" event.
     */
    public function forceDeleted(Company $company): void
    {
        //
    }
}
