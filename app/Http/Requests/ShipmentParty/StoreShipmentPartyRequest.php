<?php

namespace App\Http\Requests\ShipmentParty;

use App\Enums\StatusEnum;
use App\Http\Requests\BaseRequest;
use App\Interfaces\Company\ShipmentPartyRepositoryInterface;
use Illuminate\Validation\Rule;

class StoreShipmentPartyRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(ShipmentPartyRepositoryInterface $shipmentPartyRepository): array
    {
        return [
            'national_identifier' => [
                'required',
                'string',
                'max:20',
                $shipmentPartyRepository->uniqueNationalIdentifierRule($this->companyId()),
            ],
            'is_sender' => ['required', 'boolean'],
            'is_receiver' => ['required', 'boolean'],
            'status' => ['sometimes', 'required', Rule::enum(StatusEnum::class)],
            'title' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'landline' => ['nullable', 'string', 'max:20'],
            'intermediary_code' => ['nullable', 'string', 'max:255'],
            'transportation_code' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}
