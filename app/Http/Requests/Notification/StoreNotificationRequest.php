<?php

namespace App\Http\Requests\Notification;

use App\Enums\NotificationType;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class StoreNotificationRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(NotificationType::class)],
            'should_remove_previous' => ['sometimes', 'boolean'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeBooleanStrings(['should_remove_previous']);
    }
}
