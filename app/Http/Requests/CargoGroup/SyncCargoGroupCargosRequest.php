<?php

namespace App\Http\Requests\CargoGroup;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class SyncCargoGroupCargosRequest extends BaseRequest
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
            'cargo_ids' => ['required', 'array'],
            'cargo_ids.*' => ['required', 'integer', 'distinct:strict', Rule::exists('cargos', 'id')],
        ];
    }
}
