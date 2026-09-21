<?php

namespace App\Http\Resources\Users;

use App\Http\Resources\TreeResource;
use Illuminate\Http\Request;

class TreeUsersResource extends TreeResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->treeData($request, $this->full_name, [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'username' => $this->username,
            'national_code' => $this->national_code,
            'phone' => $this->phone,
            'parent_id' => $this->parent_id,
        ]);
    }
}
