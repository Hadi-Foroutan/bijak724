<?php

namespace App\Http\Controllers\Admin\InsuranceAgency;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InsuranceAgency\ConnectInsuranceRequest;
use App\Http\Requests\Admin\InsuranceAgency\StoreInsuranceAgencyRequest;
use App\Http\Requests\Admin\InsuranceAgency\UpdateInsuranceAgencyRequest;
use App\Http\Resources\InsuranceAgencies\TreeInsuranceAgenciesResource;
use App\Http\Resources\InsuranceAgencyResource;
use App\Models\Company;
use App\Models\Insurance;
use App\Models\InsuranceAgency;
use App\Services\Admin\InsuranceAgencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class InsuranceAgencyController extends Controller
{
    public function __construct(protected InsuranceAgencyService $agencyService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        if ($request->boolean('tree')) {
            $result = $this->agencyService->tree($request->all());

            return ResponseHandler::success(TreeInsuranceAgenciesResource::collection($result->data));
        }

        $result = $this->agencyService->index($request->all());

        return ResponseHandler::success($this->resourceCollection($result->data, InsuranceAgencyResource::class, $request));
    }

    public function store(StoreInsuranceAgencyRequest $request): JsonResponse
    {
        $result = $this->agencyService->store($request->validated());

        return ResponseHandler::success(
            InsuranceAgencyResource::make($result->data)->resolve($request),
            __('public.created_success', ['attribute' => 'نمایندگی بیمه']),
            Response::HTTP_CREATED,
        );
    }

    public function show(Request $request, InsuranceAgency $insuranceAgency): JsonResponse
    {
        $result = $this->agencyService->show($insuranceAgency->getKey());

        return ResponseHandler::success(InsuranceAgencyResource::make($result->data)->resolve($request));
    }

    public function update(UpdateInsuranceAgencyRequest $request, InsuranceAgency $insuranceAgency): JsonResponse
    {
        $result = $this->agencyService->update($insuranceAgency, $request->validated());

        return ResponseHandler::success(
            InsuranceAgencyResource::make($result->data)->resolve($request),
            __('public.update_success', ['attribute' => 'نمایندگی بیمه']),
        );
    }

    public function destroy(InsuranceAgency $insuranceAgency): JsonResponse
    {
        $result = $this->agencyService->destroy($insuranceAgency);

        return ResponseHandler::success([], $result->data);
    }

    /** The companies that have a contract connected to the agency */
    public function companies(InsuranceAgency $insuranceAgency): JsonResponse
    {
        return ResponseHandler::success($this->agencyService->companies($insuranceAgency)->data->map(fn(array $row): array => [
            'company_id' => $row['company']->getKey(),
            'name' => $row['company']->name,
            'organization_code' => $row['company']->organization_code,
            'insurances_count' => $row['insurances_count'],
            'active_insurances_count' => $row['active_count'],
        ])->all());
    }

    /** The companies not connected to the agency yet, that have a contract with its insurer (`search` = name or organization code) */
    public function unconnectedCompanies(Request $request, InsuranceAgency $insuranceAgency): JsonResponse
    {
        $result = $this->agencyService->unconnectedCompanies($insuranceAgency, $request->query('search'));

        return ResponseHandler::success($result->data->map(fn(Company $company): array => [
            'company_id' => $company->getKey(),
            'name' => $company->name,
            'organization_code' => $company->organization_code,
        ])->all());
    }

    /** The insurance contracts connected to the agency (with their company), with the state of each connection */
    public function insurances(InsuranceAgency $insuranceAgency): JsonResponse
    {
        return ResponseHandler::success($this->insurancesData($this->agencyService->insurances($insuranceAgency)->data, $insuranceAgency));
    }

    /** The contracts of a company with the agency's insurer: what the admin picks from after choosing the company */
    public function companyInsurances(InsuranceAgency $insuranceAgency, Company $company): JsonResponse
    {
        return ResponseHandler::success($this->insurancesData($this->agencyService->companyInsurances($insuranceAgency, $company->getKey())->data, $insuranceAgency));
    }

    public function connectInsurance(ConnectInsuranceRequest $request, InsuranceAgency $insuranceAgency): JsonResponse
    {
        $result = $this->agencyService->connectInsurance($insuranceAgency, (int)$request->validated('insurance_id'));

        return ResponseHandler::success($this->insurancesData($result->data, $insuranceAgency), __('public.update_success', ['attribute' => 'اتصال قرارداد بیمه']));
    }

    public function disconnectInsurance(InsuranceAgency $insuranceAgency, Insurance $insurance): JsonResponse
    {
        $result = $this->agencyService->disconnectInsurance($insuranceAgency, $insurance->getKey());

        return ResponseHandler::success($this->insurancesData($result->data, $insuranceAgency), __('public.update_success', ['attribute' => 'اتصال قرارداد بیمه']));
    }

    /**
     * `connection` is `active` (connected), `inactive` (cut off by the admin) or null (not connected to this agency);
     * `connected_insurance_agency_id` tells which agency a contract belongs to when it is not this one.
     *
     * @param Collection<int, Insurance> $insurances
     * @return list<array<string, mixed>>
     */
    private function insurancesData(Collection $insurances, InsuranceAgency $agency): array
    {
        return $insurances->map(fn(Insurance $insurance): array => [
            'insurance_id' => $insurance->getKey(),
            'title' => $insurance->title,
            'contract_number' => $insurance->contract_number,
            'status' => $insurance->status->value,
            'start_date' => $insurance->start_date?->format('Y-m-d'),
            'end_date' => $insurance->end_date?->format('Y-m-d'),
            'company_id' => $insurance->company_id,
            'company_name' => $insurance->relationLoaded('company') ? $insurance->company?->name : null,
            'organization_code' => $insurance->relationLoaded('company') ? $insurance->company?->organization_code : null,
            'connection' => $insurance->insurance_agency_id === $agency->getKey() ? $insurance->insurance_agency_status->value : null,
            'connected_insurance_agency_id' => $insurance->insurance_agency_id,
        ])->values()->all();
    }
}
