<?php

namespace App\Repositories\Company;

use App\Enums\CompanyParentEnum;
use App\Interfaces\CompanyInterface;
use App\Models\Company;
use App\Services\TreeBuilder;
use Illuminate\Database\Eloquent\Collection;

class CompanyRepository implements CompanyInterface
{
    public function __construct(
        protected TreeBuilder $treeBuilder,
    ) {}

    public function all(array $params)
    {
        return Company::searchRecords(
            $params,
            fn ($query) => $query->when(
                $this->onlyParentOptions($params),
                fn ($query) => $query
                    ->where('parent_type', CompanyParentEnum::ORIGINAL->value)
                    ->whereNull('parent_id'),
            ),
        );
    }

    public function tree(array $params): Collection
    {
        unset($params['tree'], $params['paginate']);

        /** @var Collection<int, Company> $companies */
        $companies = Company::searchRecords(
            $params,
            fn ($query) => $query->when(
                $this->onlyParentOptions($params),
                fn ($query) => $query
                    ->where('parent_type', CompanyParentEnum::ORIGINAL->value)
                    ->whereNull('parent_id'),
            ),
        );

        return $this->treeBuilder->build($companies);
    }

    public function create(array $data): Company
    {
        return Company::query()->create($data);
    }

    public function update(Company $company, array $data): Company
    {
        $company->update($data);

        return $company->refresh()->load('account');
    }

    public function delete(Company $company): void
    {
        $company->delete();
    }

    public function findByOrganizationCode(string $code): ?Company
    {
        return Company::query()->where('organization_code', $code)->first();
    }

    public function findByNationalCode(string $code): ?Company
    {
        return Company::query()->where('national_code', $code)->first();
    }

    /** @param array<string, mixed> $params */
    private function onlyParentOptions(array $params): bool
    {
        return filter_var(
            $params['parent_options'] ?? $params['is_parent'] ?? false,
            FILTER_VALIDATE_BOOL,
        );
    }
}
