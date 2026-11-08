<?php

namespace App\Services\Admin;

use App\Enums\InsuranceAgencyType;
use App\Enums\InsuranceLevel;
use App\Enums\StatusEnum;
use App\Helpers\ServiceResult;
use App\Interfaces\CityRepositoryInterface;
use App\Interfaces\InsuranceAgencyRepositoryInterface;
use App\Models\InsuranceAgency;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/** The admin's management of the insurance agents (country → province → agency) and of the insurance contracts connected to them. */
class InsuranceAgencyService
{
    public function __construct(
        protected InsuranceAgencyRepositoryInterface $agencyRepository,
        protected CityRepositoryInterface            $cityRepository,
    )
    {
    }

    public function index(array $filters): ServiceResult
    {
        return ServiceResult::success($this->agencyRepository->search($filters));
    }

    public function tree(array $filters): ServiceResult
    {
        return ServiceResult::success($this->agencyRepository->tree($filters));
    }

    public function show(int $id): ServiceResult
    {
        return ServiceResult::success($this->agencyRepository->findOrFail($id));
    }

    /**
     * The admin only adds agencies, each under a province agent (the country and province agents are generated).
     *
     * @param array<string, mixed> $data
     */
    public function store(array $data): ServiceResult
    {
        $data['status'] ??= StatusEnum::ACTIVE->value;
        $data['level'] = InsuranceLevel::Agency->value;

        return ServiceResult::success($this->agencyRepository->create($this->withIdentity($this->withCity($this->withParent($data)))));
    }

    /**
     * Fills in or edits the agent's own data; an agency may also move to another province agent.
     *
     * @param array<string, mixed> $data
     */
    public function update(InsuranceAgency $agency, array $data): ServiceResult
    {
        return ServiceResult::success($this->agencyRepository->update($agency, $this->withIdentity($this->withCity($this->withParent($data)), $agency)));
    }

    /**
     * Soft delete: the row stays, the agency no longer answers to its code and the contracts connected to it stay in the database.
     * The country and province agents are the insurer's structure and are not deleted.
     */
    public function destroy(InsuranceAgency $agency): ServiceResult
    {
        if ($agency->level !== InsuranceLevel::Agency) {
            return ServiceResult::error(__('public.insurance_agency_level_not_deletable'), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->agencyRepository->delete($agency);

        return ServiceResult::success(__('public.delete_success', ['attribute' => 'نمایندگی بیمه']));
    }

    /**
     * Makes sure every insurer has its country agent ("شرکت {insurer}") and, under it, one province agent per state
     * ("سرپرستی استان {state}"); both are legal agents whose other data is filled in later by the admin.
     * Safe to run again: only what is missing is created. The data is `{countries, provinces}`: how many were created.
     */
    public function generateLevels(): ServiceResult
    {
        $created = ['countries' => 0, 'provinces' => 0];
        $states = $this->agencyRepository->states();

        foreach ($this->agencyRepository->insuranceCompanies() as $insuranceCompany) {
            DB::transaction(function () use ($insuranceCompany, $states, &$created): void {
                $country = $this->agencyRepository->ensureCountry($insuranceCompany);
                $created['countries'] += (int)$country->wasRecentlyCreated;

                foreach ($states as $state) {
                    $created['provinces'] += (int)$this->agencyRepository->ensureProvince($country, $state)->wasRecentlyCreated;
                }
            });
        }

        return ServiceResult::success($created);
    }

    /**
     * The form sends the city `code`; the agent is linked to the city by its id. The request already checked that the code
     * exists (inside the province), so a code that finds no city here is only a city deleted in between.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function withCity(array $data): array
    {
        if (!array_key_exists('city_code', $data)) {
            return $data;
        }

        $code = $data['city_code'];
        unset($data['city_code']);

        if ($code === null) {
            return [...$data, 'city_id' => null];
        }

        $city = $this->cityRepository->findByCode((int)$code);

        if ($city === null) {
            ServiceResult::error(__('public.not_found', ['attribute' => 'شهر']), Response::HTTP_NOT_FOUND);
        }

        return [...$data, 'city_id' => $city->getKey()];
    }

    /**
     * Only the identity of the agent's type is kept: a legal agent has a company name and company id code, a natural one a
     * name and national code. A country agent is always legal.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function withIdentity(array $data, ?InsuranceAgency $agency = null): array
    {
        $type = $agency?->level === InsuranceLevel::Country
            ? InsuranceAgencyType::Legal
            : InsuranceAgencyType::from((string)($data['type'] ?? $agency?->type->value));

        $unused = $type === InsuranceAgencyType::Legal
            ? ['first_name', 'last_name', 'national_code']
            : ['company_name', 'company_national_id'];

        return [...$data, 'type' => $type->value, ...array_fill_keys($unused, null)];
    }

    /**
     * An agency takes its insurer and state from its province agent.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function withParent(array $data): array
    {
        if (!isset($data['parent_id'])) {
            return $data;
        }

        $province = $this->agencyRepository->findOrFail((int)$data['parent_id']);

        return [
            ...$data,
            'insurance_company_id' => $province->insurance_company_id,
            'state_code' => $province->state_code,
        ];
    }

    /** The companies that have a contract connected to the agency, each with its number of contracts. */
    public function companies(InsuranceAgency $agency): ServiceResult
    {
        return ServiceResult::success($this->agencyRepository->connectedCompanies($agency));
    }

    /** The companies the agency is not connected to yet (the admin picks one, then one of its contracts). */
    public function unconnectedCompanies(InsuranceAgency $agency, ?string $search = null): ServiceResult
    {
        return ServiceResult::success($this->agencyRepository->unconnectedCompanies($agency, $search));
    }

    /** The contracts connected to the agency, each with its company and the state of the connection. */
    public function insurances(InsuranceAgency $agency): ServiceResult
    {
        return ServiceResult::success($this->agencyRepository->connectedInsurances($agency));
    }

    /** The contracts of the company that the agency can be connected to (the insurer's own), with their current connection. */
    public function companyInsurances(InsuranceAgency $agency, int $companyId): ServiceResult
    {
        return ServiceResult::success($this->agencyRepository->companyInsurances($agency, $companyId));
    }

    /**
     * Connects one insurance contract of a company to the agency (or turns a cut connection on again). Only an agency
     * level agent takes contracts, only those of its own insurer, and a contract connected to another agency is refused.
     */
    public function connectInsurance(InsuranceAgency $agency, int $insuranceId): ServiceResult
    {
        $insurance = $this->agencyRepository->findInsuranceOrFail($insuranceId);

        if ($agency->level !== InsuranceLevel::Agency) {
            return ServiceResult::error(__('public.insurance_agency_level_not_connectable'), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($insurance->insurance_company_id !== $agency->insurance_company_id) {
            return ServiceResult::error(__('public.insurance_agency_insurer_mismatch'), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($insurance->insurance_agency_id !== null && $insurance->insurance_agency_id !== $agency->getKey()) {
            return ServiceResult::error(__('public.insurance_connected_to_other_agency'), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->agencyRepository->connectInsurance($agency, $insurance);

        return ServiceResult::success($this->agencyRepository->connectedInsurances($agency));
    }

    /** Cuts the connection of a contract to the agency; a contract that is not the agency's is a 404. */
    public function disconnectInsurance(InsuranceAgency $agency, int $insuranceId): ServiceResult
    {
        $insurance = $this->agencyRepository->findInsuranceOrFail($insuranceId);

        if ($insurance->insurance_agency_id !== $agency->getKey()) {
            return ServiceResult::error(__('public.not_found', ['attribute' => 'ارتباط قرارداد بیمه']), Response::HTTP_NOT_FOUND);
        }

        $this->agencyRepository->disconnectInsurance($insurance);

        return ServiceResult::success($this->agencyRepository->connectedInsurances($agency));
    }
}
