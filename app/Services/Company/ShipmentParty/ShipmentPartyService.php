<?php

namespace App\Services\Company\ShipmentParty;

use App\Enums\StatusEnum;
use App\Helpers\ServiceResult;
use App\Interfaces\Company\ShipmentPartyRepositoryInterface;
use App\Services\Company\CompanyCrudService;
use Illuminate\Validation\ValidationException;

class ShipmentPartyService extends CompanyCrudService
{
    protected string $resourceLabel = 'فرستنده/گیرنده';

    public function __construct(
        protected ShipmentPartyRepositoryInterface $shipmentPartyRepository,
    ) {}

    protected function repository(): ShipmentPartyRepositoryInterface
    {
        return $this->shipmentPartyRepository;
    }

    /** @param array<string, mixed> $data */
    public function create(int $companyId, array $data): ServiceResult
    {
        $data['status'] ??= StatusEnum::ACTIVE->value;
        $this->validateRoles(
            (bool) ($data['is_sender'] ?? false),
            (bool) ($data['is_receiver'] ?? false),
        );

        return parent::create($companyId, $data);
    }

    /** @param array<string, mixed> $data */
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

        return parent::update($companyId, $id, $data);
    }

    public function findByNationalIdentifier(int $companyId, string $nationalIdentifier): ServiceResult
    {
        return ServiceResult::success(
            $this->shipmentPartyRepository->findByNationalIdentifier($companyId, $nationalIdentifier),
        );
    }

    private function validateRoles(bool $isSender, bool $isReceiver): void
    {
        if ($isSender || $isReceiver) {
            return;
        }

        throw ValidationException::withMessages([
            'is_sender' => 'طرف حمل باید حداقل فرستنده یا گیرنده باشد.',
            'is_receiver' => 'طرف حمل باید حداقل فرستنده یا گیرنده باشد.',
        ]);
    }
}
