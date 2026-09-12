<?php

namespace App\Interfaces\Company;

use App\Models\Company\Fleet;
use Illuminate\Validation\Rules\Unique;

interface FleetRepositoryInterface extends CompanyModelRepositoryInterface
{
    public function findBySmartCardNumber(int $companyId, string $smartCardNumber): Fleet;

    public function uniqueSmartCardNumberRule(int $companyId, ?int $ignoreFleetId = null): Unique;

    public function tipBelongsToSystem(int $tipCode, int $systemId): bool;
}
