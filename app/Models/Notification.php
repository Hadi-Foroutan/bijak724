<?php

namespace App\Models;

use App\Enums\NotificationType;
use App\Traits\AdvancedSearch;
use Database\Factories\NotificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    /** @use HasFactory<NotificationFactory> */
    use AdvancedSearch, HasFactory;

    protected array $searchableFields = [
        'sender_id',
        'type',
        'should_remove_previous',
        'sender__full_name',
    ];

    protected array $globalSearchFields = ['message'];

    protected $fillable = [
        'sender_id',
        'type',
        'should_remove_previous',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'type' => NotificationType::class,
            'should_remove_previous' => 'boolean',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id')->withTrashed();
    }
}
