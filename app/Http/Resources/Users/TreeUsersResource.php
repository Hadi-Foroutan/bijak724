<?php

namespace App\Http\Resources\Users;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TreeUsersResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id2' => $this->id,
            'label' => $this->full_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'username' => $this->username,
            'national_code' => $this->national_code,
            'phone' => $this->phone,
            'parent_id' => $this->parent_id,
            'children' => $this->relationLoaded('children')
                ? $this->children
                    ->map(fn (User $user): array => self::make($user)->resolve($request))
                    ->values()
                    ->all()
                : [],
        ];
    }
}
