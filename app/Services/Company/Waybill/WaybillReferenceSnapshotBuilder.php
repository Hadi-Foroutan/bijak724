<?php

namespace App\Services\Company\Waybill;

use App\Interfaces\Company\DriverRepositoryInterface;
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
        foreach (self::REFERENCES as $idField => [$referenceType, $prefix, $snapshotFields]) {
            if (! array_key_exists($idField, $data) || $this->referenceIsUnchanged($currentWaybill, $idField, $data[$idField])) {
                continue;
            }

            $record = $data[$idField] === null
                ? null
                : $this->findReference($companyId, $referenceType, (int) $data[$idField]);

            foreach ($snapshotFields as $snapshotField => $sourceField) {
                $data["{$prefix}_{$snapshotField}"] = $record?->getAttribute($sourceField);
            }
        }

        return $data;
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
            'driver' => $this->driverRepository->findOrFail($companyId, $id),
        };
    }
}
