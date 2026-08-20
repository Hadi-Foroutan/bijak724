<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DynamicModel extends Model
{
    protected $guarded = [];

    public function setTableName(string $table): static
    {
        $this->table = $table;
        return $this;
    }
}
