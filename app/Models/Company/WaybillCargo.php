<?php

namespace App\Models\Company;

use App\Models\Cargo;
use App\Models\DynamicModel;
use App\Models\Packaging;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaybillCargo extends DynamicModel
{
    protected string $companyTableKey = 'waybill_cargos';

    protected $fillable = [
        'owner_company_id',
        'waybill_id',
        'cargo_id',
        'packaging_id',
        'product_owner_id',
        'description',
        'title',
        'origin_weight',
        'value',
        'quantity',
        'is_traffic',
        'is_returned',
        'cottage_number',
        'cottage_number_2',
        'driver_account_number',
        'container_number',
        'container_number_2',
    ];

    protected array $defaultRelations = ['cargo', 'packaging', 'productOwner'];

    protected function casts(): array
    {
        return [
            'origin_weight' => 'decimal:3',
            'is_traffic' => 'boolean',
            'is_returned' => 'boolean',
        ];
    }

    public function waybill(): BelongsTo
    {
        return $this->belongsToCompany(Waybill::class, 'waybill_id', relationName: 'waybill');
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class);
    }

    public function packaging(): BelongsTo
    {
        return $this->belongsTo(Packaging::class);
    }

    public function productOwner(): BelongsTo
    {
        return $this->belongsToCompany(ProductOwner::class, 'product_owner_id', relationName: 'productOwner');
    }
}
