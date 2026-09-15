<?php

namespace App\Services\Company\Fleet;

use App\Enums\FleetOwnershipType;
use App\Enums\StatusEnum;
use App\Helpers\ServiceResult;
use App\Interfaces\Company\FleetRepositoryInterface;
use App\Services\Company\CompanyCrudService;
use Illuminate\Validation\ValidationException;

class FleetService extends CompanyCrudService
{
    protected string $resourceLabel = 'ناوگان';

    public function __construct(
        protected FleetRepositoryInterface $fleetRepository,
    ) {}

    protected function repository(): FleetRepositoryInterface
    {
        return $this->fleetRepository;
    }

    /** @param array<string, string> $plate */
    public function findByPlate(int $companyId, array $plate): ServiceResult
    {
        return ServiceResult::success(
            $this->fleetRepository->findByPlate($companyId, $plate),
        );
    }

    /** @param array<string, mixed> $data */
    public function create(int $companyId, array $data): ServiceResult
    {
        $this->validateUniquePlate($companyId, $data);
        $data['status'] ??= StatusEnum::ACTIVE->value;
        $data['ownership_type'] ??= FleetOwnershipType::Unknown->value;
        $data['has_violation'] ??= false;
        $this->validateSystemAndTip(
            $this->nullableInteger($data['system_id'] ?? null),
            $this->nullableInteger($data['tip_code'] ?? null),
        );

        return parent::create($companyId, $data);
    }

    /** @param array<string, mixed> $data */
    public function update(int $companyId, int $id, array $data): ServiceResult
    {
        foreach (['status', 'ownership_type', 'has_violation'] as $defaultedField) {
            if (array_key_exists($defaultedField, $data) && $data[$defaultedField] === null) {
                unset($data[$defaultedField]);
            }
        }

        $fleet = $this->fleetRepository->findOrFail($companyId, $id);
        $this->validateUniquePlate($companyId, array_replace($fleet->only([
            'plate_first_number',
            'plate_second_letter',
            'plate_third_number',
            'plate_fourth_number',
        ]), $data), $id);
        $systemId = array_key_exists('system_id', $data)
            ? $this->nullableInteger($data['system_id'])
            : $this->nullableInteger($fleet->getAttribute('system_id'));
        $tipCode = array_key_exists('tip_code', $data)
            ? $this->nullableInteger($data['tip_code'])
            : $this->nullableInteger($fleet->getAttribute('tip_code'));

        $this->validateSystemAndTip($systemId, $tipCode);

        return parent::update($companyId, $id, $data);
    }

    /** @param array<string, mixed> $data */
    private function validateUniquePlate(int $companyId, array $data, ?int $ignoreFleetId = null): void
    {
        if ($this->fleetRepository->plateExists($companyId, $data, $ignoreFleetId)) {
            throw ValidationException::withMessages([
                'plate_first_number' => __('public.duplicate_fleet_plate'),
            ]);
        }
    }

    private function validateSystemAndTip(?int $systemId, ?int $tipCode): void
    {
        if ($tipCode === null) {
            return;
        }

        if ($systemId === null) {
            throw ValidationException::withMessages([
                'system_id' => __('public.fleet_system_required'),
            ]);
        }

        if (! $this->fleetRepository->tipBelongsToSystem($tipCode, $systemId)) {
            throw ValidationException::withMessages([
                'tip_code' => __('public.fleet_tip_system_mismatch'),
            ]);
        }
    }

    private function nullableInteger(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }
}
