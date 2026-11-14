<?php

namespace App\Models;

use App\Enums\SmsRecipientParty;
use Illuminate\Database\Eloquent\Model;

class SmsTemplate extends Model
{
    protected $fillable = [
        'company_id',
        'party',
        'body',
        'last_updated_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'party' => SmsRecipientParty::class,
        ];
    }
}
