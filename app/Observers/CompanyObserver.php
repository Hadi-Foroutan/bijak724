<?php

namespace App\Observers;

use App\Enums\CompanyParentEnum;
use App\Models\Company;
use App\Services\Company\BijakNumber\BijakNumberService;
use App\Services\Company\CompanyTableService;
use App\Services\Company\Insurance\DefaultInsuranceService;
use App\Services\Company\ReferralNumber\ReferralNumberService;
use App\Services\Company\Settings\CompanySettingService;
use App\Services\Company\TransportContract\DefaultTransportContractService;

class CompanyObserver
{
    public function __construct(
        protected CompanyTableService $companyTableService,
        protected DefaultTransportContractService $defaultTransportContractService,
        protected DefaultInsuranceService $defaultInsuranceService,
        protected BijakNumberService $bijakNumberService,
        protected ReferralNumberService $referralNumberService,
        protected CompanySettingService $companySettingService,
    ) {}

    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        $this->defaultTransportContractService->createForCompany($company);

        if ($company->parent_type !== CompanyParentEnum::BRANCH->value) {
            $this->companyTableService->sync($company->id);
        }

        $this->bijakNumberService->ensureDefaultForCompany($company->id);
        $this->referralNumberService->ensureDefaultForCompany($company->id);
        $this->defaultInsuranceService->createForCompany($company);
        $this->companySettingService->ensureDefaultsForCompany($company->id);
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
