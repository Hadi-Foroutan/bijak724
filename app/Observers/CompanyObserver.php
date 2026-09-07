<?php

namespace App\Observers;

use App\Enums\CompanyParentEnum;
use App\Models\Company;
use App\Services\Company\CompanyTableService;
use App\Services\Company\TransportContract\DefaultTransportContractService;

class CompanyObserver
{
    public function __construct(
        protected CompanyTableService $companyTableService,
        protected DefaultTransportContractService $defaultTransportContractService,
    ) {}

    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        $this->defaultTransportContractService->createForCompany($company);

        if ($company->parent_type === CompanyParentEnum::BRANCH->value) {
            return;
        }

        $this->companyTableService->sync($company->id);
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
