<?php

namespace App\Http\Controllers\Admin\Company;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreCompanyRequest;
use App\Http\Requests\Company\UpdateCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Services\Company\CompanyService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanyController extends Controller
{
    public function __construct(
        protected CompanyService $companyService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $res = $this->companyService->index($request->all());

        return ResponseHandler::success($this->companiesResource($res->data, $request));
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $res = $this->companyService->create($request->validated());

        return ResponseHandler::success(
            CompanyResource::make($res->data)->resolve($request),
            __('public.created_success', ['attribute' => 'شرکت']),
            Response::HTTP_CREATED,
        );
    }

    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        $res = $this->companyService->update($company, $request->validated());

        return ResponseHandler::success(CompanyResource::make($res->data)->resolve($request));
    }

    public function show(Request $request, Company $company): JsonResponse
    {
        return ResponseHandler::success(
            CompanyResource::make($company->load('account'))->resolve($request),
        );
    }

    public function destroy(Company $company): JsonResponse
    {
        $res = $this->companyService->delete($company);

        return ResponseHandler::success([], $res->data);
    }

    private function companiesResource(mixed $companies, Request $request): mixed
    {
        if ($companies instanceof LengthAwarePaginator) {
            return $companies->through(
                fn (Company $company): array => CompanyResource::make($company)->resolve($request),
            );
        }

        return CompanyResource::collection($companies)->resolve($request);
    }
}
