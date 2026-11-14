<?php

namespace App\Http\Requests\Sms;

use App\Enums\SmsTemplatePlaceholder;
use App\Http\Requests\BaseRequest;
use Closure;

class SaveSmsTemplateRequest extends BaseRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'body' => ['bail', 'required', 'string', 'max:1000', function (string $attribute, string $value, Closure $fail): void {
                $unknown = array_diff(SmsTemplatePlaceholder::keysIn($value), SmsTemplatePlaceholder::values());

                if ($unknown !== []) {
                    $fail(__('public.sms_template_unknown_placeholder', ['keys' => implode('، ', $unknown)]));
                }
            }],
        ];
    }
}
