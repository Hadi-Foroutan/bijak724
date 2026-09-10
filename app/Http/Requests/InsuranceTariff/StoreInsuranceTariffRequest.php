<?php

namespace App\Http\Requests\InsuranceTariff;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class StoreInsuranceTariffRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'cargo_group_id' => ['required', 'integer', Rule::exists('cargo_groups', 'id')],
            'cargo_value_from' => ['required', 'numeric', 'min:0'],
            'cargo_value_to' => ['nullable', 'numeric', 'gte:cargo_value_from'],
            'fixed_premium' => ['nullable', 'numeric', 'min:0', 'required_without:premium_percentage'],
            'premium_percentage' => ['nullable', 'numeric', 'between:0,100', 'required_without:fixed_premium'],
            'excess_amount' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ];
    }
}
