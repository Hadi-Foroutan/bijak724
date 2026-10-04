<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->companyNumberTableOwnerIds() as $companyId) {
            $referralTable = "company_{$companyId}_referral_numbers";
            $bijakTable = "company_{$companyId}_bijak_numbers";

            if (! Schema::hasTable($bijakTable) && Schema::hasTable($referralTable)) {
                Schema::rename($referralTable, $bijakTable);
            }

            $this->replaceIndexes($bijakTable, $referralTable, $bijakTable);

            if (! Schema::hasTable($referralTable)) {
                $this->createNumberTable($referralTable);
            }

            if (DB::table($referralTable)->doesntExist()) {
                $this->seedDefaultReferralNumbers($companyId, $referralTable);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->companyNumberTables('bijak_numbers') as $bijakTable) {
            $companyId = $this->companyIdFromTable($bijakTable);
            $referralTable = "company_{$companyId}_referral_numbers";

            Schema::dropIfExists($referralTable);
            $this->replaceIndexes($bijakTable, $bijakTable, $referralTable);
            Schema::rename($bijakTable, $referralTable);
        }
    }

    /** @return list<string> */
    private function companyNumberTables(string $suffix): array
    {
        return collect(Schema::getTables())
            ->pluck('name')
            ->filter(fn (string $tableName): bool => preg_match(
                "/^company_\\d+_{$suffix}$/",
                $tableName,
            ) === 1)
            ->values()
            ->all();
    }

    private function companyIdFromTable(string $tableName): int
    {
        preg_match('/^company_(\d+)_/', $tableName, $matches);

        return (int) $matches[1];
    }

    /** @return list<int> */
    private function companyNumberTableOwnerIds(): array
    {
        return collect([
            ...$this->companyNumberTables('referral_numbers'),
            ...$this->companyNumberTables('bijak_numbers'),
        ])
            ->map(fn (string $tableName): int => $this->companyIdFromTable($tableName))
            ->unique()
            ->values()
            ->all();
    }

    private function replaceIndexes(string $tableName, string $fromPrefix, string $toPrefix): void
    {
        $oldOwnerIndex = "{$fromPrefix}_owner_company_id_index";
        $newOwnerIndex = "{$toPrefix}_owner_company_id_index";
        $oldActiveIndex = "{$fromPrefix}_active_unique";
        $newActiveIndex = "{$toPrefix}_active_unique";

        if ($oldActiveIndex !== $newActiveIndex && Schema::hasIndex($tableName, $oldActiveIndex)) {
            Schema::table($tableName, function (Blueprint $table) use ($oldActiveIndex): void {
                $table->dropUnique($oldActiveIndex);
            });
        }

        if ($oldOwnerIndex !== $newOwnerIndex && Schema::hasIndex($tableName, $oldOwnerIndex)) {
            Schema::table($tableName, function (Blueprint $table) use ($oldOwnerIndex): void {
                $table->dropIndex($oldOwnerIndex);
            });
        }

        if (! Schema::hasIndex($tableName, $newOwnerIndex)) {
            Schema::table($tableName, function (Blueprint $table) use ($newOwnerIndex): void {
                $table->index('owner_company_id', $newOwnerIndex);
            });
        }

        if (! Schema::hasIndex($tableName, $newActiveIndex)) {
            Schema::table($tableName, function (Blueprint $table) use ($newActiveIndex): void {
                $table->unique(['owner_company_id', 'active_slot'], $newActiveIndex);
            });
        }
    }

    private function createNumberTable(string $tableName): void
    {
        Schema::create($tableName, function (Blueprint $table) use ($tableName): void {
            $table->id();
            $table->unsignedBigInteger('owner_company_id')->index();
            $table->string('title');
            $table->string('serial_number');
            $table->unsignedBigInteger('from_number');
            $table->unsignedBigInteger('to_number');
            $table->unsignedBigInteger('last_number')->nullable();
            $table->enum('status', ['active', 'inactive', 'completed'])->default('active');
            $table->unsignedInteger('active_slot')->nullable();
            $table->timestamps();

            $table->unique(['owner_company_id', 'active_slot'], "{$tableName}_active_unique");
        });
    }

    private function seedDefaultReferralNumbers(int $dataOwnerCompanyId, string $tableName): void
    {
        $now = now();
        $rows = collect($this->companyIdsOwnedBy($dataOwnerCompanyId))
            ->map(fn (int $companyId): array => [
                'owner_company_id' => $companyId,
                'title' => 'پیش فرض',
                'serial_number' => '1405',
                'from_number' => 100000,
                'to_number' => 999999,
                'last_number' => null,
                'status' => 'active',
                'active_slot' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($rows !== []) {
            DB::table($tableName)->insert($rows);
        }
    }

    /** @return list<int> */
    private function companyIdsOwnedBy(int $dataOwnerCompanyId): array
    {
        $companies = DB::table('companies')
            ->select(['id', 'parent_id'])
            ->get()
            ->keyBy('id');

        return $companies
            ->filter(function (object $company) use ($companies, $dataOwnerCompanyId): bool {
                $current = $company;
                $visited = [];

                while ($current->parent_id !== null) {
                    if (isset($visited[$current->id]) || ! $companies->has($current->parent_id)) {
                        return false;
                    }

                    $visited[$current->id] = true;
                    $current = $companies->get($current->parent_id);
                }

                return (int) $current->id === $dataOwnerCompanyId;
            })
            ->keys()
            ->map(fn (int|string $companyId): int => (int) $companyId)
            ->values()
            ->all();
    }
};
