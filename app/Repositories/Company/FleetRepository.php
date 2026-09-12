<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\FleetRepositoryInterface;
use App\Models\Company\Fleet;
use App\Models\FleetType;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class FleetRepository extends CompanyModelRepository implements FleetRepositoryInterface
{
    protected string $tableKey = 'fleets';

    public function findBySmartCardNumber(int $companyId, string $smartCardNumber): Fleet
    {
        /** @var Fleet $fleet */
        $fleet = $this->query($companyId)
            ->where('smart_card_number', $smartCardNumber)
            ->firstOrFail();

        /** @var Fleet */
        return $this->loadRelations($fleet);
    }

    public function uniqueSmartCardNumberRule(int $companyId, ?int $ignoreFleetId = null): Unique
    {
        $rule = Rule::unique($this->tableRegistry->tableName($companyId, $this->tableKey), 'smart_card_number');

        return $ignoreFleetId === null ? $rule : $rule->ignore($ignoreFleetId);
    }

    public function tipBelongsToSystem(int $tipCode, int $systemId): bool
    {
        return FleetType::query()
            ->where('tip_code', $tipCode)
            ->whereHas('brand', fn ($query) => $query->whereKey($systemId))
            ->exists();
    }
}
