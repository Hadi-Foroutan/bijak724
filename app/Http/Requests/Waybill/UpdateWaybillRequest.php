<?php

namespace App\Http\Requests\Waybill;

class UpdateWaybillRequest extends StoreWaybillRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return collect($this->waybillRules())
            ->map(fn (array $rules): array => ['sometimes', ...$rules])
            ->all();
    }
}
