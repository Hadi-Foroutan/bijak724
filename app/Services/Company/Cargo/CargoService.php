<?php

namespace App\Services\Company\Cargo;

use App\Interfaces\Company\CargoRepositoryInterface;
use App\Models\Company\Cargo;
use App\Services\Company\CompanyCrudService;

/** @extends CompanyCrudService<Cargo, CargoRepositoryInterface> */
class CargoService extends CompanyCrudService
{
    protected string $resourceLabel = 'محموله';

    public function __construct(protected CargoRepositoryInterface $cargoRepository) {}

    protected function repository(): CargoRepositoryInterface
    {
        return $this->cargoRepository;
    }
}
