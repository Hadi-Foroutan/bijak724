<?php

namespace App\Models;

use App\Traits\AdvancedSearch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class DynamicModel extends Model
{
    use AdvancedSearch;

    protected $guarded = [];

    /** @var list<string> */
    protected array $searchableFields = [];

    /** @var list<string> */
    protected array $globalSearchFields = [];

    protected string $companyTableKey = '';

    /** @var list<string> */
    protected array $defaultRelations = [];

    public function forCompany(int $companyId, ?string $tableKey = null): static
    {
        $resolvedTableKey = $tableKey ?? $this->companyTableKey;

        if ($resolvedTableKey === '') {
            throw new LogicException('A company table key must be provided.');
        }

        return $this->setTableName("company_{$companyId}_{$resolvedTableKey}");
    }

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

    /** @return list<string> */
    public function defaultRelations(): array
    {
        return $this->defaultRelations;
    }

    public function companyId(): int
    {
        if (preg_match('/^company_(\d+)_/', $this->getTable(), $matches) !== 1) {
            throw new LogicException("Company ID cannot be resolved from table [{$this->getTable()}].");
        }

        return (int) $matches[1];
    }

    /**
     * @param  class-string<DynamicModel>  $related
     */
    protected function belongsToCompany(
        string $related,
        string $foreignKey,
        string $ownerKey = 'id',
        string $relationName = '',
    ): BelongsTo {
        $instance = $this->newCompanyRelatedInstance($related);

        return $this->newBelongsTo(
            $instance->newQuery(),
            $this,
            $foreignKey,
            $ownerKey,
            $relationName,
        );
    }

    /**
     * @param  class-string<DynamicModel>  $related
     */
    protected function hasManyCompany(
        string $related,
        string $foreignKey,
        string $localKey = 'id',
    ): HasMany {
        $instance = $this->newCompanyRelatedInstance($related);

        return $this->newHasMany(
            $instance->newQuery(),
            $this,
            $instance->qualifyColumn($foreignKey),
            $localKey,
        );
    }

    /**
     * @param  class-string<DynamicModel>  $related
     */
    private function newCompanyRelatedInstance(string $related): DynamicModel
    {
        $instance = new $related;
        $instance->setConnection($this->getConnectionName());

        return $instance->forCompany($this->companyId());
    }
}
