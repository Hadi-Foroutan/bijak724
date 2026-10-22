<?php

namespace App\Models;

use App\Enums\NotificationType;
use App\Traits\AdvancedSearch;
use Database\Factories\NotificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
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
        'recipient_user_id',
        'recipient_company_id',
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

    public function recipientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id')->withTrashed();
    }

    public function recipientCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'recipient_company_id');
    }

    public function isPublic(): bool
    {
        return $this->recipient_user_id === null && $this->recipient_company_id === null;
    }

    /** اعلان‌های عمومی + اعلان‌های اختصاصی همین کاربر یا شرکت او. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user): void {
            $query->where(function (Builder $query): void {
                $query->whereNull('recipient_user_id')->whereNull('recipient_company_id');
            })->orWhere('recipient_user_id', $user->getKey());

            if ($user->company_id !== null) {
                $query->orWhere('recipient_company_id', $user->company_id);
            }
        });
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id')->withTrashed();
    }
}
