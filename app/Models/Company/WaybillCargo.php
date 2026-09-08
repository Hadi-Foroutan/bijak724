<?php

namespace App\Models\Company;

use App\Models\Cargo;
use App\Models\DynamicModel;
use App\Models\Packaging;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaybillCargo extends DynamicModel
{
    protected string $companyTableKey = 'waybill_cargos';

    protected array $defaultRelations = ['cargo', 'packaging'];

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
}
