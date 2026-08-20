<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PermissionGroup extends Model
{
    protected $fillable = [
        'name',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'permissions_groups',
            'permission_group_id',
            'permission_id'
        )->withTimestamps();
    }
}
