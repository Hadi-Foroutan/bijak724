<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\FleetRepositoryInterface;
use App\Models\Company\Fleet;

class FleetRepository extends CompanyModelRepository implements FleetRepositoryInterface
{
    protected string $modelClass = Fleet::class;

    public function findBySmartCardNumber(int $companyId, string $smartCardNumber): Fleet
    {
        /** @var Fleet $fleet */
        $fleet = $this->query($companyId)
            ->where('smart_card_number', $smartCardNumber)
            ->firstOrFail();

        /** @var Fleet */
        return $this->loadRelations($fleet);
    }
}
