<?php

namespace App\Http\Requests\ShipmentParty;

use App\Enums\StatusEnum;
use App\Http\Requests\BaseRequest;
use App\Services\Company\CompanyDataService;
use Illuminate\Validation\Rule;

class UpdateShipmentPartyRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $table = app(CompanyDataService::class)->table($this->companyId(), 'shipment_parties');

        return [
            'national_identifier' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique($table, 'national_identifier')->ignore((int) $this->route('shipmentParty')),
            ],
            'is_sender' => ['sometimes', 'required', 'boolean'],
            'is_receiver' => ['sometimes', 'required', 'boolean'],
            'status' => ['sometimes', 'required', Rule::enum(StatusEnum::class)],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'first_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mobile' => ['sometimes', 'nullable', 'string', 'max:20'],
            'landline' => ['sometimes', 'nullable', 'string', 'max:20'],
            'intermediary_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'transportation_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
