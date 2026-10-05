<?php

namespace App\Http\Requests\TransportContract;

use App\Http\Requests\BaseRequest;
use App\Models\User;
use App\Services\Company\TransportContract\TransportContractAccessService;

class SyncTransportContractUsersRequest extends BaseRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && app(TransportContractAccessService::class)->canViewAll($user);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'user_ids' => ['present', 'array'],
            'user_ids.*' => ['integer', 'distinct:strict'],
        ];
    }
}
