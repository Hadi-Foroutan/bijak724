<?php

namespace App\Http\Requests\InsuranceTariff;

use App\Http\Requests\BaseRequest;
use App\Models\InsuranceTariff;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateInsuranceTariffRequest extends BaseRequest
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
            'cargo_group_id' => ['sometimes', 'required', 'integer', Rule::exists('cargo_groups', 'id')],
            'cargo_value_from' => ['sometimes', 'required', 'numeric', 'min:0'],
            'cargo_value_to' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'fixed_premium' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'premium_percentage' => ['sometimes', 'nullable', 'numeric', 'between:0,100'],
            'excess_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $tariff = InsuranceTariff::query()
                ->whereKey($this->route('tariff'))
                ->whereHas('insurance', fn ($query) => $query
                    ->whereKey($this->route('insurance'))
                    ->where('company_id', $this->companyId()))
                ->first();

            if ($tariff === null) {
                return;
            }

            $fixedPremium = $this->exists('fixed_premium') ? $this->input('fixed_premium') : $tariff->fixed_premium;
            $premiumPercentage = $this->exists('premium_percentage') ? $this->input('premium_percentage') : $tariff->premium_percentage;

            if ($fixedPremium === null && $premiumPercentage === null) {
                $validator->errors()->add('fixed_premium', 'مبلغ ثابت یا درصد حق بیمه الزامی است.');
                $validator->errors()->add('premium_percentage', 'مبلغ ثابت یا درصد حق بیمه الزامی است.');
            }

            $from = (float) $this->input('cargo_value_from', $tariff->cargo_value_from);
            $to = $this->exists('cargo_value_to') ? $this->input('cargo_value_to') : $tariff->cargo_value_to;

            if ($to !== null && (float) $to < $from) {
                $validator->errors()->add('cargo_value_to', 'حد بالای ارزش محموله نباید کمتر از حد پایین باشد.');
            }
        });
    }
}
