<?php

namespace App\Services\Company\ShipmentParty;

use App\Helpers\ServiceResult;
use App\Interfaces\Company\ShipmentPartyAddressRepositoryInterface;
use App\Interfaces\Company\ShipmentPartyRepositoryInterface;

class ShipmentPartyAddressService
{
    public function __construct(
        protected ShipmentPartyAddressRepositoryInterface $addressRepository,
        protected ShipmentPartyRepositoryInterface $shipmentPartyRepository,
    ) {}

    /** @param array<string, mixed> $params */
    public function index(
        int $companyId,
        ?int $shipmentPartyId,
        array $params,
    ): ServiceResult {
        $shipment  = $this->shipmentPartyRepository->find($companyId, $shipmentPartyId);

        if (!$shipment) {
            return ServiceResult::error(__('public.not_found', ['attribute' => 'دریافتی پرداختی']));
        }

        return ServiceResult::success(
            $this->addressRepository->searchForParty($companyId, $shipmentPartyId, $params),
        );
    }

    /** @param array<string, mixed> $data */
    public function create(
        int $companyId,
        int $shipmentPartyId,
        array $data,
    ): ServiceResult {
        $this->shipmentPartyRepository->findOrFail($companyId, $shipmentPartyId);
        $data['shipment_party_id'] = $shipmentPartyId;

        return ServiceResult::success($this->addressRepository->create($companyId, $data));
    }

    public function show(int $companyId, int $shipmentPartyId, int $addressId): ServiceResult
    {
        return ServiceResult::success(
            $this->addressRepository->findForPartyOrFail($companyId, $shipmentPartyId, $addressId),
        );
    }

    /** @param array<string, mixed> $data */
    public function update(
        int $companyId,
        int $shipmentPartyId,
        int $addressId,
        array $data,
    ): ServiceResult {
        unset($data['shipment_party_id']);

        return ServiceResult::success(
            $this->addressRepository->updateForParty(
                $companyId,
                $shipmentPartyId,
                $addressId,
                $data,
            ),
        );
    }

    public function delete(int $companyId, int $shipmentPartyId, int $addressId): ServiceResult
    {
        $this->addressRepository->deleteForParty($companyId, $shipmentPartyId, $addressId);

        return ServiceResult::success(
            __('public.delete_success', ['attribute' => 'آدرس']),
        );
    }
}
