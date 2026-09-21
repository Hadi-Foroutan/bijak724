<?php

namespace App\Repositories\Company;

use App\Interfaces\Company\FleetRepositoryInterface;
use App\Models\Company\Fleet;
use App\Models\FleetType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/** @extends CompanyModelRepository<Fleet> */
class FleetRepository extends CompanyModelRepository implements FleetRepositoryInterface
{
    protected string $modelClass = Fleet::class;

    /** @param array<string, string> $plate */
    public function findByPlate(int $companyId, array $plate): Fleet
    {
        /** @var Fleet $fleet */
        $fleet = $this->plateQuery($companyId, $plate)->firstOrFail();

        /** @var Fleet */
        return $this->loadRelations($fleet);
    }

    /** @param array<string, string> $plate */
    public function plateExists(int $companyId, array $plate, ?int $ignoreFleetId = null): bool
    {
        $query = $this->plateQuery($companyId, $plate, true);

        if ($ignoreFleetId !== null) {
            $query->whereKeyNot($ignoreFleetId);
        }

        return $query->exists();
    }

    /** @param array<string, string> $plate */
    private function plateQuery(int $companyId, array $plate, bool $shared = false): Builder
    {
        $query = $shared ? $this->sharedQuery($companyId) : $this->query($companyId);

        return $query
            ->where('plate_first_number', $plate['plate_first_number'])
            ->where('plate_second_letter', $plate['plate_second_letter'])
            ->where('plate_third_number', $plate['plate_third_number'])
            ->where('plate_fourth_number', $plate['plate_fourth_number']);
    }

    public function uniqueSmartCardNumberRule(int $companyId, ?int $ignoreFleetId = null): Unique
    {
        $rule = Rule::unique($this->tableName($companyId), 'smart_card_number');

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
