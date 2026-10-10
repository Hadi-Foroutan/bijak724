<?php

namespace App\Services\Company\CanceledReferral;

use App\Helpers\ServiceResult;
use App\Interfaces\Company\CanceledReferralRepositoryInterface;

class CanceledReferralService
{
    public function __construct(
        protected CanceledReferralRepositoryInterface $canceledReferralRepository,
    ) {}

    /** @param array<string, mixed> $filters */
    public function index(int $companyId, array $filters): ServiceResult
    {
        return ServiceResult::success(
            $this->canceledReferralRepository->search($companyId, $filters),
        );
    }
}
