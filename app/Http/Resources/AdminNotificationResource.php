<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminNotificationResource extends JsonResource
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
            'sender' => $this->whenLoaded('sender', fn(): array => [
                'id' => $this->sender->id,
                'full_name' => $this->sender->full_name,
                'username' => $this->sender->username,
                'email' => $this->sender->email,
            ]),
            'recipient_user' => $this->whenLoaded('recipientUser', fn(): ?array => $this->recipientUser === null ? null : [
                'id' => $this->recipientUser->id,
                'full_name' => $this->recipientUser->full_name,
            ]),
            'recipient_company' => $this->whenLoaded('recipientCompany', fn(): ?array => $this->recipientCompany === null ? null : [
                'id' => $this->recipientCompany->id,
                'name' => $this->recipientCompany->name,
            ]),
            'is_private' => ! $this->isPublic(),
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'should_remove_previous' => $this->should_remove_previous,
            'message' => $this->message,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
