<?php

namespace App\Services\Company;

use App\Interfaces\CompanyDataRepositoryInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CompanyTableService
{
    public function __construct(
        protected CompanyDataRepositoryInterface $companyDataRepository,
    ) {}

    public function createCompanyTables(int $companyId, array $schemas): void
    {
        foreach ($schemas as $tableKey => $columns) {
            $tableName = $this->companyDataRepository->table($companyId, $tableKey);

            Schema::create($tableName, function (Blueprint $table) use ($columns) {
                $table->id();

                foreach ($columns as $column) {
                    $this->addColumn($table, $column);
                }

                $table->timestamps();
            });
        }
    }

    private function addColumn(Blueprint $table, array $column): void
    {
        $definition = match ($column['type']) {
            'string' => $table->string($column['name'], $column['length'] ?? 255),
            'integer' => $table->integer($column['name']),
            'text' => $table->text($column['name']),
            'json' => $table->json($column['name']),
            'boolean' => $table->boolean($column['name']),
            'date' => $table->date($column['name']),
            'enum' => $table->enum($column['name'], $column['values']),
            'unsignedBigInteger' => $table->unsignedBigInteger($column['name']),
            'unsignedSmallInteger' => $table->unsignedSmallInteger($column['name']),
            default => throw new \InvalidArgumentException("Unsupported company table column type [{$column['type']}]."),
        };

        if ($column['nullable'] ?? true) {
            $definition->nullable();
        }

        if (array_key_exists('default', $column)) {
            $definition->default($column['default']);
        } elseif ($column['type'] === 'boolean') {
            $definition->default(false);
        }

        if ($column['unique'] ?? false) {
            $definition->unique();
        } elseif ($column['index'] ?? false) {
            $definition->index();
        }

        if (isset($column['foreign'])) {
            $table->foreign($column['name'])
                ->references($column['foreign']['column'])
                ->on($column['foreign']['table'])
                ->restrictOnDelete();
        }
    }
}
