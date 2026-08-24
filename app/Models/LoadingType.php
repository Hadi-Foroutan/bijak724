<?php

namespace App\Models;

use App\Enums\StatusEnum;
use Illuminate\Database\Eloquent\Model;

class LoadingType extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'min_weight',
        'max_weight',
        'specially_fale',
        'loader_link_typeCode',
        'loader_link_typeTitle',
        'type_code',
        'is_updated',
        'type_desc',
        'status',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_updated' => false,
        'status' => StatusEnum::ACTIVE->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_updated' => 'boolean',
            'status' => StatusEnum::class,
        ];
    }
}
