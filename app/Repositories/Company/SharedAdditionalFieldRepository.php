<?php

namespace App\Repositories\Company;

use App\Enums\AdditionalFieldLayout;
use App\Enums\AdditionalFieldType;
use App\Interfaces\Company\SharedAdditionalFieldRepositoryInterface;
use App\Models\SharedAdditionalField;
use Illuminate\Database\Eloquent\Collection;

class SharedAdditionalFieldRepository implements SharedAdditionalFieldRepositoryInterface
{
    public function __construct(protected SharedAdditionalField $sharedAdditionalField)
    {
    }

    public function all(int $companyId): Collection
    {
        return $this->sharedAdditionalField->newQuery()
            ->where('company_id', $companyId)
            ->orderBy('number')
            ->get();
    }

    public function findOrFail(int $companyId, int $id): SharedAdditionalField
    {
        return $this->sharedAdditionalField->newQuery()
            ->where('company_id', $companyId)
            ->findOrFail($id);
    }

    public function update(SharedAdditionalField $field, array $data): SharedAdditionalField
    {
        unset($data['company_id'], $data['number']);
        $field->update($data);

        return $field->refresh();
    }

    public function ensureDefault(int $companyId, int $number): SharedAdditionalField
    {
        return $this->sharedAdditionalField->newQuery()->firstOrCreate(
            [
                'company_id' => $companyId,
                'number' => $number,
            ],
            [
                'title' => SharedAdditionalField::DEFAULT_TITLES[$number],
                'is_visible' => false,
                'is_visible_in_print' => false,
                'field_type' => AdditionalFieldType::Text,
                'length' => 1,
                'layout' => AdditionalFieldLayout::Rtl,
                'default_value' => null,
                'description' => null,
            ],
        );
    }
}
