<?php

namespace App\Interfaces\Company;

use App\Models\Company\Fleet;
use Illuminate\Validation\Rules\Unique;

/** @extends CompanyModelRepositoryInterface<Fleet> */
interface FleetRepositoryInterface extends CompanyModelRepositoryInterface
{
    /** @param array<string, string> $plate */
    public function findByPlate(int $companyId, array $plate): Fleet;

    /** @param array<string, string> $plate */
    public function plateExists(int $companyId, array $plate, ?int $ignoreFleetId = null): bool;

    public function uniqueSmartCardNumberRule(int $companyId, ?int $ignoreFleetId = null): Unique;

    public function tipBelongsToSystem(int $tipCode, int $systemId): bool;
}
