<?php

namespace App\Services\InsurancePanel;

use App\Helpers\ServiceResult;
use App\Interfaces\InsurancePanelRepositoryInterface;
use App\Models\InsuranceAgency;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * The insurance panel: a user of an insurer sees the companies that recorded insurance contracts in the user's scope
 * (country / province / agency, see InsurancePanelRepositoryInterface) and only the waybills those contracts insured.
 */
class InsurancePanelService
{
    public function __construct(
        protected InsurancePanelRepositoryInterface $panelRepository,
        protected InsuranceAccessService            $accessService,
    )
    {
    }

    /** @param array<string, mixed> $filters */
    public function companies(User $viewer, array $filters): ServiceResult
    {
        return ServiceResult::success($this->panelRepository->companies($this->scopeOf($viewer), $filters));
    }

    /** @param array<string, mixed> $filters */
    public function waybills(User $viewer, int $companyId, array $filters): ServiceResult
    {
        return ServiceResult::success($this->panelRepository->waybills(
            $companyId,
            $this->insuranceIds($viewer, $companyId),
            $filters,
        ));
    }

    public function waybill(User $viewer, int $companyId, int $id): ServiceResult
    {
        return ServiceResult::success($this->panelRepository->findWaybill(
            $companyId,
            $this->insuranceIds($viewer, $companyId),
            $id,
        ));
    }

    /** @return list<int> the contracts of the company the user may see; the company does not exist for the user when there are none */
    private function insuranceIds(User $viewer, int $companyId): array
    {
        $ids = $this->panelRepository->insuranceIds($this->scopeOf($viewer), $companyId);

        if ($ids === [] || $this->panelRepository->findCompany($companyId) === null) {
            ServiceResult::error(__('public.not_found', ['attribute' => 'شرکت']), Response::HTTP_NOT_FOUND);
        }

        return $ids;
    }

    /** The agency whose scope applies (for an admin inside an agency's panel, that agency); a user without an agency is refused. */
    private function scopeOf(User $user): InsuranceAgency
    {
        $scope = $this->accessService->scope($user);

        if ($scope !== null) {
            return $scope;
        }

        if ($this->accessService->isSupportToken($this->accessService->currentToken($user))) {
            ServiceResult::error(__('public.insurance_agency_not_found'), Response::HTTP_NOT_FOUND);
        }

        ServiceResult::error(__('public.access_denied', ['attribute' => 'پنل بیمه']), Response::HTTP_FORBIDDEN);
    }
}
