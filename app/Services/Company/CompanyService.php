<?php

namespace App\Services\Company;

use App\Helpers\ServiceResult;
use App\Interfaces\CompanyInterface;
use App\Interfaces\UserInterface;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

class CompanyService
{
    public function __construct(
        protected CompanyInterface $companyRepository,
        protected UserInterface    $userRepository,
    ) {}

    public function index(array $params): ServiceResult
    {
        return ServiceResult::success($this->companyRepository->all($params));
    }

    public function create(array $data): ServiceResult
    {
        $data['panel_code'] = random_int(10000, 99999);
        $company = $this->companyRepository->create($data);

        return ServiceResult::success($company);
    }

    public function update(Company $company, array $data): ServiceResult
    {
        return ServiceResult::success($this->companyRepository->update($company, $data));
    }

    public function delete(Company $company): ServiceResult
    {
        $this->companyRepository->delete($company);

        return ServiceResult::success(__('public.delete_success', ['attribute' => 'شرکت']));
    }
}
