<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** The access key one user sends SMS with; having a row is what "registered" means for that user. */
class SmsPanelUser extends Model
{
    protected $fillable = [
        'sms_panel_id',
        'user_id',
        'api_key',
    ];

    protected $hidden = [
        'api_key',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
        ];
    }
}
