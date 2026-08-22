<?php

namespace App\Http\Requests\Permission;

use App\Enums\StatusEnum;
use App\Http\Requests\BaseRequest;
use App\Models\Permission;
use Illuminate\Validation\Rule;

class StorePermissionRequest extends BaseRequest
{
    private const NAME_PATTERN = '/^[A-Za-z]+(?:-[A-Za-z]+)*(?:\.[A-Za-z]+(?:-[A-Za-z]+)*){2,3}$/';

    public function rules(): array
    {

        $uniqueName = Rule::unique(Permission::class, 'name');
        $role = $this->route('permission');

        if ($role instanceof Permission) {
            $uniqueName->ignore($role);
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:'.self::NAME_PATTERN,
                $uniqueName,
            ],
            'display_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['in:' . implode(',',StatusEnum::values())],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'نام دسترسی باید فقط شامل حروف انگلیسی و خط تیره باشد و با دو یا سه نقطه بخش‌بندی شود.',
        ];
    }
}
