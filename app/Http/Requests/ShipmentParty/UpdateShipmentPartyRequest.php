<?php

namespace App\Http\Requests\ShipmentParty;

use App\Enums\StatusEnum;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;
use App\Models\Company\ShipmentParty;

class UpdateShipmentPartyRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'national_identifier' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                $this->uniqueInCompany(ShipmentParty::class, 'national_identifier', $this->routeModelId('shipmentParty')),
            ],
            'is_sender' => ['sometimes', 'required', 'boolean'],
            'is_receiver' => ['sometimes', 'required', 'boolean'],
            'status' => ['sometimes', 'required', Rule::enum(StatusEnum::class)],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'first_name' => ['required', 'nullable', 'string', 'max:255'],
            'last_name' => ['required', 'nullable', 'string', 'max:255'],
            'mobile' => ['sometimes', 'nullable', 'string', 'max:20'],
            'landline' => ['sometimes', 'nullable', 'string', 'max:20'],
            'intermediary_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'transportation_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
