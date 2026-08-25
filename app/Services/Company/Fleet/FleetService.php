<?php

namespace App\Services\Company\Fleet;

use App\Enums\StatusEnum;
use App\Helpers\ServiceResult;
use App\Interfaces\CompanyDataRepositoryInterface;
use App\Models\DriverLicenseType;
use App\Models\DynamicModel;
use App\Models\FleetBrand;
use App\Models\FleetType;
use App\Models\LoadingType;
use Illuminate\Support\Collection;

class FleetService
{
    private const TABLE = 'fleets';

    public function __construct(
        protected CompanyDataRepositoryInterface $companyDataRepository,
    ) {}

    public function index(int $companyId, array $params): ServiceResult
    {
        $query = $this->companyDataRepository
            ->query($companyId, self::TABLE)
            ->when(
                $params['search'] ?? null,
                function ($query, string $search): void {
                    $query->where(function ($query) use ($search): void {
                        $query->where('smart_card_number', 'like', "%{$search}%")
                            ->orWhere('owner_mobile', 'like', "%{$search}%")
                            ->orWhere('chassis_number', 'like', "%{$search}%")
                            ->orWhere('engine_number', 'like', "%{$search}%")
                            ->orWhere('vin', 'like', "%{$search}%")
                            ->orWhere('plate_two_digits', 'like', "%{$search}%")
                            ->orWhere('plate_three_digits', 'like', "%{$search}%")
                            ->orWhere('plate_ir_number', 'like', "%{$search}%");
                    });
                },
            )
            ->when(
                isset($params['status']),
                fn ($query) => $query->where('status', $params['status']),
            )
            ->when(
                isset($params['ownership_type']),
                fn ($query) => $query->where('ownership_type', $params['ownership_type']),
            )
            ->when(
                isset($params['fleet_brand_id']),
                fn ($query) => $query->where('fleet_brand_id', $params['fleet_brand_id']),
            )
            ->when(
                isset($params['loading_type_id']),
                fn ($query) => $query->where('loading_type_id', $params['loading_type_id']),
            )
            ->latest('id');

        $perPage = min(max((int) ($params['per_page'] ?? 15), 1), 100);
        $fleets = $query->paginate($perPage);

        $this->loadReferences($fleets->getCollection());

        return ServiceResult::success($fleets);
    }

    public function create(int $companyId, array $data): ServiceResult
    {
        $data['status'] ??= StatusEnum::ACTIVE->value;
        $data['has_violation'] ??= false;

        $fleet = $this->companyDataRepository->create($companyId, self::TABLE, $data);

        return ServiceResult::success($this->loadFleetReferences($fleet));
    }

    public function show(int $companyId, int $fleetId): ServiceResult
    {
        return ServiceResult::success(
            $this->loadFleetReferences($this->findFleet($companyId, $fleetId)),
        );
    }

    public function findBySmartCardNumber(int $companyId, string $smartCardNumber): ServiceResult
    {
        $fleet = $this->companyDataRepository
            ->query($companyId, self::TABLE)
            ->where('smart_card_number', $smartCardNumber)
            ->firstOrFail();

        return ServiceResult::success($this->loadFleetReferences($fleet));
    }

    public function update(int $companyId, int $fleetId, array $data): ServiceResult
    {
        $fleet = $this->findFleet($companyId, $fleetId);

        if (array_key_exists('fleet_brand_id', $data)
            && ! array_key_exists('fleet_type_code', $data)) {
            $data['fleet_type_code'] = null;
        }

        $fleet->update($data);

        return ServiceResult::success($this->loadFleetReferences($fleet->refresh()));
    }

    public function delete(int $companyId, int $fleetId): ServiceResult
    {
        $this->findFleet($companyId, $fleetId)->delete();

        return ServiceResult::success(__('public.delete_success', ['attribute' => 'ناوگان']));
    }

    private function findFleet(int $companyId, int $fleetId): DynamicModel
    {
        /** @var DynamicModel $fleet */
        $fleet = $this->companyDataRepository
            ->query($companyId, self::TABLE)
            ->findOrFail($fleetId);

        return $fleet;
    }

    private function loadFleetReferences(DynamicModel $fleet): DynamicModel
    {
        $this->loadReferences(collect([$fleet]));

        return $fleet;
    }

    /** @param Collection<int, DynamicModel> $fleets */
    private function loadReferences(Collection $fleets): void
    {
        $driverLicenseTypes = DriverLicenseType::query()
            ->findMany($fleets->pluck('driver_license_type_id')->filter()->unique())
            ->keyBy('id');
        $loadingTypes = LoadingType::query()
            ->findMany($fleets->pluck('loading_type_id')->filter()->unique())
            ->keyBy('id');
        $fleetBrands = FleetBrand::query()
            ->findMany($fleets->pluck('fleet_brand_id')->filter()->unique())
            ->keyBy('id');
        $fleetTypes = FleetType::query()
            ->findMany($fleets->pluck('fleet_type_code')->filter()->unique())
            ->keyBy('tip_code');

        $fleets->each(function (DynamicModel $fleet) use (
            $driverLicenseTypes,
            $loadingTypes,
            $fleetBrands,
            $fleetTypes,
        ): void {
            $fleet->setRelation(
                'driverLicenseType',
                $driverLicenseTypes->get($fleet->driver_license_type_id),
            );
            $fleet->setRelation('loadingType', $loadingTypes->get($fleet->loading_type_id));
            $fleet->setRelation('fleetBrand', $fleetBrands->get($fleet->fleet_brand_id));
            $fleet->setRelation('fleetType', $fleetTypes->get($fleet->fleet_type_code));
        });
    }
}
