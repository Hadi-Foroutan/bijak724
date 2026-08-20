<?php

namespace App\Http\Requests\City;

use App\Http\Requests\BaseRequest;
use App\Models\City;
use Illuminate\Validation\Rule;

class UpdateCityRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var City $city */
        $city = $this->route('city');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'sometimes',
                'required',
                'integer',
                'min:0',
                Rule::unique(City::class)->ignore($city),
            ],
            'state_id' => ['sometimes', 'required', 'integer', 'exists:states,id'],
            'tax_id' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'tax_ostan' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'anbar_code' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
