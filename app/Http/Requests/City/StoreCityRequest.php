<?php

namespace App\Http\Requests\City;

use App\Http\Requests\BaseRequest;
use App\Models\City;
use Illuminate\Validation\Rule;

class StoreCityRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'integer', 'min:0', Rule::unique(City::class)],
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'tax_id' => ['nullable', 'integer', 'min:0'],
            'tax_ostan' => ['nullable', 'integer', 'min:0'],
            'anbar_code' => ['nullable', 'string', 'max:255'],
        ];
    }
}
