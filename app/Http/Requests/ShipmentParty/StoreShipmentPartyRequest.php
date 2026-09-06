<?php

namespace App\Http\Requests\ShipmentParty;

use App\Enums\StatusEnum;
use App\Http\Requests\BaseRequest;
use App\Services\Company\CompanyDataService;
use Illuminate\Validation\Rule;

class StoreShipmentPartyRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $table = app(CompanyDataService::class)->table($this->companyId(), 'shipment_parties');

        return [
            'national_identifier' => ['required', 'string', 'max:20', Rule::unique($table, 'national_identifier')],
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
