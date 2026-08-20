<?php

namespace App\Services\Company;

use App\Helpers\ResponseHandler;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanyContextService
{
    private const REQUEST_ATTRIBUTE = 'current_company';

    public function __construct(
        protected CompanySupportTokenService $supportTokenService,
    ) {}

    public function company(Request $request): Company
    {
        $resolvedCompany = $request->attributes->get(self::REQUEST_ATTRIBUTE);

        if ($resolvedCompany instanceof Company) {
            return $resolvedCompany;
        }

        $user = $request->user();

        if (! $user instanceof User) {
            $this->denyAccess();
        }

        $companyId = $this->supportTokenService->companyId($user);

        if ($companyId === null) {
            $this->denyAccess();
        }

        if (! $this->supportTokenService->isSupportToken($user)
            && (int) $user->company_id !== $companyId) {
            $this->denyAccess();
        }

        $company = Company::query()->find($companyId);

        if (! $company instanceof Company) {
            $this->denyAccess();
        }

        $request->attributes->set(self::REQUEST_ATTRIBUTE, $company);

        return $company;
    }

    public function companyId(Request $request): int
    {
        return (int) $this->company($request)->getKey();
    }

    private function denyAccess(): never
    {
        throw new HttpResponseException(
            ResponseHandler::error(
                __('public.access_denied', ['attribute' => 'شرکت']),
                status: Response::HTTP_FORBIDDEN,
            )
        );
    }
}
