<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RoleEnum;
use App\Enums\UserStatusEnum;
use App\Traits\AdvancedSearch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use AdvancedSearch, HasApiTokens, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'parent_id',
        'national_code',
        'full_name',
        'first_name',
        'last_name',
        'print_name',
        'phone',
        'email',
        'username',
        'password',
        'min_commission_percentage',
        'max_commission_percentage',
        'address',
        'signature_image',
        'profile_image',
        'description',
        'status',
    ];

    protected array $searchableFields = [
        'company_id',
        'parent_id',
        'national_code',
        'full_name',
        'first_name',
        'last_name',
        'print_name',
        'phone',
        'email',
        'username',
        'password',
        'min_commission_percentage',
        'max_commission_percentage',
        'status',
    ];

    protected array $globalSearchFields = [
        'national_code',
        'full_name',
        'first_name',
        'last_name',
        'print_name',
        'phone',
        'email',
        'username',
    ];

    protected $hidden = [
        'password',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected static function booted(): void
    {
        static::saving(function ($user) {
            if ($user->isDirty(['first_name', 'last_name'])) {
                $user->full_name = $user->makeFullName();
            }
        });
    }

    private function makeFullName(): ?string
    {
        $first = trim($this->first_name ?? '');
        $last = trim($this->last_name ?? '');

        return trim($first.' '.$last) ?: null;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->with('children');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'user_permissions',
            'user_id',
            'permission_id'
        )->withTimestamps();
    }

    public function hasAnyRole(): bool
    {
        return $this->roles()->get()->isNotEmpty();
    }

    public function isSuperAdmin(): bool
    {
        return $this->roles()->where('name', RoleEnum::SUPERADMIN->value)->exists();
    }

    public function isActive(): bool
    {
        return $this->status == UserStatusEnum::ACTIVE->value ?? false;
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    public function getPermissions(): array
    {
        // اگر سوپر ادمینه
        if ($this->isSuperAdmin()) {
            return Permission::pluck('name')->toArray();
        }

        return $this->permissions()
            ->pluck('name')
            ->unique()
            ->values()
            ->toArray();
    }
}
