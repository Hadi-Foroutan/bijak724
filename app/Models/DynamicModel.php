<?php

namespace App\Models;

use App\Services\Company\CompanyContextService;
use App\Services\Company\CompanyDataOwnerResolver;
use App\Traits\AdvancedSearch;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use LogicException;

abstract class DynamicModel extends Model
{
    use AdvancedSearch;

    protected $guarded = [];

    /** @var list<string> */
    protected array $searchableFields = [];

    /** @var list<string> */
    protected array $globalSearchFields = [];

    protected string $companyTableKey = '';

    protected ?int $companyContextId = null;

    /** @var list<string> */
    protected array $defaultRelations = [];

    public function companyTableKey(): string
    {
        if ($this->companyTableKey === '') {
            throw new LogicException('A company table key must be configured on the model.');
        }

        return $this->companyTableKey;
    }

    public function forCompany(int $companyId): static
    {
        $this->companyContextId = $companyId;
        $dataOwnerCompanyId = app(CompanyDataOwnerResolver::class)->resolveId($companyId);
        $columns = collect($this->companyTableColumns());

        return $this
            ->setTableName("company_{$dataOwnerCompanyId}_{$this->companyTableKey()}")
            ->setSearchableFields(
                array_values(array_unique([
                    ...$this->getSearchableFields(),
                    ...$columns
                        ->filter(fn (array $column): bool => (bool) ($column['searchable'] ?? true))
                        ->pluck('name')
                        ->filter()
                        ->values()
                        ->all(),
                ])),
            )
            ->setGlobalSearchFields(
                $columns
                    ->filter(fn (array $column): bool => (bool) ($column['global_search'] ?? in_array(
                        Arr::get($column, 'type'),
                        ['string', 'text'],
                        true,
                    )))
                    ->pluck('name')
                    ->filter()
                    ->values()
                    ->all(),
            );
    }

    public function newInstanceForCompany(int $companyId): static
    {
        $model = $this->newInstance();
        $model->setConnection($this->getConnectionName());

        return $model->forCompany($companyId);
    }

    /** @return Builder<static> */
    public function newQueryForCompany(int $companyId): Builder
    {
        $model = $this->newInstanceForCompany($companyId);
        $query = $model->newQuery();

        if (! app(CompanyDataOwnerResolver::class)->isDataOwner($companyId)) {
            $query->where($model->qualifyColumn('owner_company_id'), $companyId);
        }

        return $query;
    }

    /** @return Builder<static> */
    public function newSharedQueryForCompany(int $companyId): Builder
    {
        $dataOwnerCompanyId = app(CompanyDataOwnerResolver::class)->resolveId($companyId);
        $model = $this->newInstanceForCompany($dataOwnerCompanyId);

        return $model->newQuery();
    }

    /** @return Builder<static> */
    public static function queryForCompany(int $companyId): Builder
    {
        return (new static)->newQueryForCompany($companyId);
    }

    public static function modelForCompany(int $companyId): static
    {
        return (new static)->newInstanceForCompany($companyId);
    }

    /** @return Builder<static> */
    public static function sharedQueryForCompany(int $companyId): Builder
    {
        return (new static)->newSharedQueryForCompany($companyId);
    }

    /** @param array<string, mixed> $attributes */
    public static function createForCompany(
        int $companyId,
        array $attributes,
        bool $loadDefaultRelations = true,
    ): static {
        $model = static::modelForCompany($companyId)
            ->newQuery()
            ->create([...$attributes, 'owner_company_id' => $companyId]);

        return $loadDefaultRelations ? $model->loadDefaultRelations() : $model;
    }

    /**
     * @param  null|Closure(Builder): (Builder|void)  $queryCallback
     */
    public static function findForCompanyOrFail(
        int $companyId,
        int $id,
        ?Closure $queryCallback = null,
    ): static {
        $model = new static;
        $query = $model->newQueryForCompany($companyId)
            ->with($model->defaultRelations());

        if ($queryCallback !== null) {
            $callbackResult = $queryCallback($query);

            if ($callbackResult instanceof Builder) {
                $query = $callbackResult;
            }
        }

        return $query->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  null|Closure(Builder): (Builder|void)  $queryCallback
     */
    public static function updateForCompany(
        int $companyId,
        int $id,
        array $attributes,
        ?Closure $queryCallback = null,
    ): static {
        unset($attributes['owner_company_id']);

        $model = static::findForCompanyOrFail($companyId, $id, $queryCallback);
        $model->fill($attributes);

        if ($model->isDirty()) {
            $model->save();
        }

        return $model->refresh()->loadDefaultRelations();
    }

    /**
     * @param  null|Closure(Builder): (Builder|void)  $queryCallback
     */
    public static function deleteForCompany(
        int $companyId,
        int $id,
        ?Closure $queryCallback = null,
    ): void {
        $query = static::queryForCompany($companyId);

        if ($queryCallback !== null) {
            $callbackResult = $queryCallback($query);

            if ($callbackResult instanceof Builder) {
                $query = $callbackResult;
            }
        }

        $query->findOrFail($id)->delete();
    }

    /**
     * Search records in the current company's dynamic table.
     *
     * @param  array<string, mixed>  $filters
     * @param  null|Closure(Builder): (Builder|void)  $queryCallback
     * @return Collection<int, static>|LengthAwarePaginator
     */
    public static function searchRecordsForCompany(
        int $companyId,
        array $filters,
        ?Closure $queryCallback = null,
    ): Collection|LengthAwarePaginator {
        $model = new static;
        $query = $model->newQueryForCompany($companyId)
            ->with($model->defaultRelations())
            ->advancedSearch($filters);

        if ($queryCallback !== null) {
            $callbackResult = $queryCallback($query);

            if ($callbackResult instanceof Builder) {
                $query = $callbackResult;
            }
        }

        return $query->getModel()->advancedSearchResults($query, $filters);
    }

    public function resolveRouteBinding($value, $field = null): ?static
    {
        $companyId = app(CompanyContextService::class)->companyId(request());

        return $this->newQueryForCompany($companyId)
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();
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

    /** @return list<string> */
    public static function defaultRelationsForCompany(): array
    {
        return (new static)->defaultRelations();
    }

    public function loadDefaultRelations(): static
    {
        if ($this->defaultRelations !== []) {
            $this->loadMissing($this->defaultRelations);
        }

        return $this;
    }

    public function companyId(): int
    {
        if (preg_match('/^company_(\d+)_/', $this->getTable(), $matches) !== 1) {
            throw new LogicException("Company ID cannot be resolved from table [{$this->getTable()}].");
        }

        return (int) $matches[1];
    }

    public function companyContextId(): int
    {
        $ownerCompanyId = $this->getAttribute('owner_company_id');

        return $ownerCompanyId === null
            ? ($this->companyContextId ?? $this->companyId())
            : (int) $ownerCompanyId;
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
            $this->companyRelationQuery($instance),
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
            $this->companyRelationQuery($instance),
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

        return $instance->forCompany($this->companyContextId());
    }

    private function companyRelationQuery(DynamicModel $instance): Builder
    {
        $query = $instance->newQuery();
        $companyId = $this->companyContextId();

        if (! app(CompanyDataOwnerResolver::class)->isDataOwner($companyId)) {
            $query->where($instance->qualifyColumn('owner_company_id'), $companyId);
        }

        return $query;
    }

    /** @return list<array<string, mixed>> */
    private function companyTableColumns(): array
    {
        $definition = config("company_tables.{$this->companyTableKey()}", []);

        if (! is_array($definition)) {
            throw new LogicException("Columns for company table [{$this->companyTableKey()}] must be an array.");
        }

        $columns = Arr::isList($definition) ? $definition : ($definition['columns'] ?? []);

        if (! is_array($columns)) {
            throw new LogicException("Columns for company table [{$this->companyTableKey()}] must be an array.");
        }

        return array_values($columns);
    }
}
