<?php

namespace App\Services\Company\Waybill;

use App\Helpers\ServiceResult;
use App\Interfaces\Company\WaybillRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

class IssuedWaybillDeletionGuard
{
    public function __construct(
        protected WaybillRepositoryInterface $waybillRepository,
    ) {}

    /** @param list<string> $columns */
    public function ensureReferenceCanBeDeleted(
        int $companyId,
        array $columns,
        int $referenceId,
        string $attribute,
    ): void {
        if (! $this->waybillRepository->hasIssuedReference($companyId, $columns, $referenceId)) {
            return;
        }

        $this->throwDeletionError($attribute);
    }

    public function ensureCargoReferenceCanBeDeleted(
        int $companyId,
        string $column,
        int $referenceId,
        string $attribute,
    ): void {
        if (! $this->waybillRepository->hasIssuedCargoReference($companyId, $column, $referenceId)) {
            return;
        }

        $this->throwDeletionError($attribute);
    }

    private function throwDeletionError(string $attribute): never
    {
        ServiceResult::error(
            __('public.issued_waybill_reference_delete_forbidden', ['attribute' => $attribute]),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }
}
