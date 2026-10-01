<?php

namespace App\Http\Requests\Waybill;

use App\Enums\WaybillStatus;
use App\Interfaces\Company\DriverRepositoryInterface;
use App\Interfaces\Company\FleetRepositoryInterface;
use App\Interfaces\Company\InsuranceRepositoryInterface;
use App\Interfaces\Company\ProductOwnerRepositoryInterface;
use App\Interfaces\Company\ShipmentPartyAddressRepositoryInterface;
use App\Interfaces\Company\ShipmentPartyRepositoryInterface;
use App\Interfaces\Company\TransportContractRepositoryInterface;
use Illuminate\Validation\Rule;

class UpdateWaybillRequest extends StoreWaybillRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(
        ShipmentPartyRepositoryInterface $shipmentPartyRepository,
        ShipmentPartyAddressRepositoryInterface $shipmentPartyAddressRepository,
        DriverRepositoryInterface $driverRepository,
        FleetRepositoryInterface $fleetRepository,
        InsuranceRepositoryInterface $insuranceRepository,
        TransportContractRepositoryInterface $transportContractRepository,
        ProductOwnerRepositoryInterface $productOwnerRepository,
    ): array {
        $rules = parent::rules(
            $shipmentPartyRepository,
            $shipmentPartyAddressRepository,
            $driverRepository,
            $fleetRepository,
            $insuranceRepository,
            $transportContractRepository,
            $productOwnerRepository,
        );

        $rules['status'] = [
            'required',
            Rule::enum(WaybillStatus::class),
            Rule::in([
                WaybillStatus::Incomplete->value,
                WaybillStatus::Referral->value,
            ]),
        ];
        $rules['bijak_number'] = ['prohibited'];
        $rules['serial_number'] = ['prohibited'];
        $rules['bijak_tracking_code'] = ['prohibited'];

        return $rules;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'status.in' => __('public.waybill_update_status_invalid'),
        ];
    }
}
