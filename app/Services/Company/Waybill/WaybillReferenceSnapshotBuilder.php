<?php

namespace App\Services\Company\Waybill;

use App\Enums\WaybillStatus;
use App\Interfaces\Company\DriverRepositoryInterface;
use App\Interfaces\Company\ShipmentPartyAddressRepositoryInterface;
use App\Interfaces\Company\ShipmentPartyRepositoryInterface;
use App\Models\Company\Waybill;
use App\Models\DynamicModel;

class WaybillReferenceSnapshotBuilder
{
    private const REFERENCES = [
        'sender_id' => ['shipment_party', 'sender', [
            'national_identifier' => 'national_identifier',
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'mobile' => 'mobile',
        ]],
        'receiver_id' => ['shipment_party', 'receiver', [
            'national_identifier' => 'national_identifier',
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'mobile' => 'mobile',
        ]],
        'sender_address_id' => ['shipment_party_address', 'sender_address', [
            'postal_code' => 'postal_code',
            'phone' => 'phone',
            'city_code' => 'city_code',
            'address' => 'address',
            'description' => 'description',
        ]],
        'receiver_address_id' => ['shipment_party_address', 'receiver_address', [
            'postal_code' => 'postal_code',
            'phone' => 'phone',
            'city_code' => 'city_code',
            'address' => 'address',
            'description' => 'description',
        ]],
        'driver1_id' => ['driver', 'driver1', [
            'national_code' => 'national_code',
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'phone' => 'phone_number_1',
        ]],
        'driver2_id' => ['driver', 'driver2', [
            'national_code' => 'national_code',
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'phone' => 'phone_number_1',
        ]],
        'referral_driver_id' => ['driver', 'referral_driver', [
            'national_code' => 'national_code',
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'phone' => 'phone_number_1',
        ]],
    ];

    public function __construct(
        protected ShipmentPartyRepositoryInterface $shipmentPartyRepository,
        protected ShipmentPartyAddressRepositoryInterface $shipmentPartyAddressRepository,
        protected DriverRepositoryInterface $driverRepository,
    ) {}

    /** @param array<string, mixed> $data */
    public function forCreate(int $companyId, array $data): array
    {
        return $this->build($companyId, $data);
    }

    /** @param array<string, mixed> $data */
    public function forUpdate(int $companyId, Waybill $waybill, array $data): array
    {
        return $this->build($companyId, $data, $waybill);
    }

    /** @param array<string, mixed> $data */
    private function build(int $companyId, array $data, ?Waybill $currentWaybill = null): array
    {
        $isFinalizing = $this->isFinalizing($currentWaybill, $data);

        foreach (self::REFERENCES as $idField => [$referenceType, $prefix, $snapshotFields]) {
            $referenceWasProvided = array_key_exists($idField, $data);

            if (! $referenceWasProvided && ! $isFinalizing) {
                continue;
            }

            $referenceId = $referenceWasProvided
                ? $data[$idField]
                : $currentWaybill?->getAttribute($idField);

            if (! $isFinalizing && $this->referenceIsUnchanged($currentWaybill, $idField, $referenceId)) {
                continue;
            }

            $record = $referenceId === null
                ? null
                : $this->findReference($companyId, $referenceType, (int) $referenceId);

            foreach ($snapshotFields as $snapshotField => $sourceField) {
                $data["{$prefix}_{$snapshotField}"] = $record?->getAttribute($sourceField);
            }
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    private function isFinalizing(?Waybill $waybill, array $data): bool
    {
        if ($waybill === null || ! array_key_exists('status', $data)) {
            return false;
        }

        $newStatus = WaybillStatus::tryFrom((string) $data['status']);
        $currentStatus = $waybill->status instanceof WaybillStatus
            ? $waybill->status
            : WaybillStatus::tryFrom((string) $waybill->status);
        $finalStatuses = [WaybillStatus::Completed, WaybillStatus::Canceled];

        return in_array($newStatus, $finalStatuses, true)
            && ! in_array($currentStatus, $finalStatuses, true);
    }

    private function referenceIsUnchanged(?Waybill $waybill, string $idField, mixed $newId): bool
    {
        if ($waybill === null) {
            return false;
        }

        $currentId = $waybill->getAttribute($idField);

        return $currentId === null || $newId === null
            ? $currentId === $newId
            : (int) $currentId === (int) $newId;
    }

    private function findReference(int $companyId, string $referenceType, int $id): DynamicModel
    {
        return match ($referenceType) {
            'shipment_party' => $this->shipmentPartyRepository->findOrFail($companyId, $id),
            'shipment_party_address' => $this->shipmentPartyAddressRepository->findOrFail($companyId, $id),
            'driver' => $this->driverRepository->findOrFail($companyId, $id),
        };
    }
}
