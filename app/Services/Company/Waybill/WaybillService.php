<?php

namespace App\Services\Company\Waybill;

use App\Helpers\ServiceResult;
use App\Interfaces\Company\WaybillRepositoryInterface;
use App\Models\Company\Waybill;
use App\Models\TransportContract;
use App\Services\Company\CompanyCrudService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class WaybillService extends CompanyCrudService
{
    protected string $resourceLabel = 'بارنامه';

    public function __construct(
        protected WaybillRepositoryInterface $waybillRepository,
        protected WaybillReferenceSnapshotBuilder $snapshotBuilder,
        protected WaybillFinancialCalculator $financialCalculator,
        protected WaybillTrackingCodeGenerator $trackingCodeGenerator,
    ) {}

    protected function repository(): WaybillRepositoryInterface
    {
        return $this->waybillRepository;
    }

    public function options(int $companyId): ServiceResult
    {
        $contracts = $this->waybillRepository->transportContractOptions($companyId);

        return ServiceResult::success([
            'transport_contracts' => $contracts->map(fn (TransportContract $contract): array => [
                'id' => $contract->id,
                'title' => $contract->title,
                'contract_number' => $contract->contract_number,
                'items' => $contract->items->map(fn ($item): array => [
                    'name' => $item->name->value,
                    'primary_value' => $item->primary_value === null ? null : (float) $item->primary_value,
                ])->values(),
            ]),
        ]);
    }

    /** @param array<string, mixed> $data */
    public function create(int $companyId, array $data): ServiceResult
    {
        return DB::transaction(function () use ($companyId, $data): ServiceResult {
            $cargos = Arr::pull($data, 'cargos', []);
            $data = $this->snapshotBuilder->forCreate($companyId, $data);
            $data['bijak_tracking_code'] = $this->trackingCodeGenerator->generate($companyId);
            $data = $this->financialCalculator->calculate($companyId, $data);

            /** @var Waybill $waybill */
            $waybill = $this->waybillRepository->create($companyId, $data);
            $this->waybillRepository->syncCargos($waybill, $companyId, $cargos);

            return ServiceResult::success($this->waybillRepository->findOrFail($companyId, $waybill->getKey()));
        });
    }

    /** @param array<string, mixed> $data */
    public function update(int $companyId, int $id, array $data): ServiceResult
    {
        return DB::transaction(function () use ($companyId, $id, $data): ServiceResult {
            /** @var Waybill $waybill */
            $waybill = $this->waybillRepository->findOrFail($companyId, $id);
            $cargos = Arr::pull($data, 'cargos', []);
            $data = $this->snapshotBuilder->forUpdate($companyId, $waybill, $data);
            $data = $this->financialCalculator->calculate($companyId, $data);

            /** @var Waybill $waybill */
            $waybill = $this->waybillRepository->update($companyId, $id, $data);

            $this->waybillRepository->syncCargos($waybill, $companyId, $cargos);

            return ServiceResult::success($this->waybillRepository->findOrFail($companyId, $id));
        });
    }
}
