<?php

namespace App\Models;

use App\Traits\AdvancedSearch;
use Illuminate\Database\Eloquent\Model;

class DynamicModel extends Model
{
    use AdvancedSearch;

    protected $guarded = [];

    /** @var list<string> */
    protected array $searchableFields = [];

    /** @var list<string> */
    protected array $globalSearchFields = [];

    public function setTableName(string $table): static
    {
        $this->table = $table;

        return $this;
    }

    /** @param list<string> $fields */
    public function setSearchableFields(array $fields): static
    {
        $this->searchableFields = $fields;

        return $this;
    }

    /** @param list<string> $fields */
    public function setGlobalSearchFields(array $fields): static
    {
        $this->globalSearchFields = $fields;

        return $this;
    }
}
