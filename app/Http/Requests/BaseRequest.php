<?php

namespace App\Http\Requests;

use App\Helpers\ResponseHandler;
use App\Models\Company;
use App\Services\Company\CompanyContextService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->toArray();

        throw new HttpResponseException(
            ResponseHandler::error($errors, __('public.validation_error'))
        );
    }

    protected function company(): Company
    {
        return app(CompanyContextService::class)->company($this);
    }

    protected function companyId(): int
    {
        return app(CompanyContextService::class)->companyId($this);
    }
}
