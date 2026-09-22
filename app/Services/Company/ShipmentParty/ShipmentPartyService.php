<?php

namespace App\Services\Company\ShipmentParty;

use App\Enums\StatusEnum;
use App\Helpers\ServiceResult;
use App\Interfaces\Company\ShipmentPartyRepositoryInterface;
use Illuminate\Validation\ValidationException;

class ShipmentPartyService
{
    public function __construct(
        protected ShipmentPartyRepositoryInterface $shipmentPartyRepository,
    ) {}

    public function index(int $companyId, array $params): ServiceResult
    {
        return ServiceResult::success($this->shipmentPartyRepository->search($companyId, $params));
    }

    public function create(int $companyId, array $data): ServiceResult
    {
        $data['status'] ??= StatusEnum::ACTIVE->value;
        $this->validateRoles(
            (bool) ($data['is_sender'] ?? false),
            (bool) ($data['is_receiver'] ?? false),
        );

        return ServiceResult::success($this->shipmentPartyRepository->create($companyId, $data));
    }

    public function show(int $companyId, int $id): ServiceResult
    {
        return ServiceResult::success($this->shipmentPartyRepository->findOrFail($companyId, $id));
    }

    public function update(int $companyId, int $id, array $data): ServiceResult
    {
        $shipmentParty = $this->shipmentPartyRepository->findOrFail($companyId, $id);
        $isSender = array_key_exists('is_sender', $data)
            ? (bool) $data['is_sender']
            : (bool) $shipmentParty->getAttribute('is_sender');
        $isReceiver = array_key_exists('is_receiver', $data)
            ? (bool) $data['is_receiver']
            : (bool) $shipmentParty->getAttribute('is_receiver');

        $this->validateRoles($isSender, $isReceiver);

        return ServiceResult::success(
            $this->shipmentPartyRepository->update($companyId, $id, $data),
        );
    }

    public function delete(int $companyId, int $id): ServiceResult
    {
        $this->shipmentPartyRepository->delete($companyId, $id);

        return ServiceResult::success(
            __('public.delete_success', ['attribute' => 'فرستنده/گیرنده']),
        );
    }

    public function findByNationalIdentifierAndType(
        int $companyId,
        string $nationalIdentifier,
        string $type,
    ): ServiceResult {
        return ServiceResult::success(
            $this->shipmentPartyRepository->findByNationalIdentifierAndType(
                $companyId,
                $nationalIdentifier,
                $type,
            ),
        );
    }

    private function validateRoles(bool $isSender, bool $isReceiver): void
    {
        if ($isSender || $isReceiver) {
            return;
        }

        throw ValidationException::withMessages([
            'is_sender' => __('public.shipment_party_role_required'),
            'is_receiver' => __('public.shipment_party_role_required'),
        ]);
    }
}
