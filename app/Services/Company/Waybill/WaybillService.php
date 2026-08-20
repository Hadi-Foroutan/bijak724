<?php

namespace App\Services\Company\Waybill;

use App\Interfaces\CompanyDataRepositoryInterface;

class WaybillService
{
    public function __construct(
        protected CompanyDataRepositoryInterface $repo
    ) {}

    public function create(int $companyId, array $data)
    {
        return $this->repo->create($companyId, 'waybills', $data);
    }

    public function list(int $companyId)
    {
        return $this->repo->query($companyId, 'waybills')->paginate();
    }
}
