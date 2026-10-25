<?php

use App\Enums\StatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Waybill columns for the chosen technical manager and the snapshot taken when the waybill is issued.
     * New companies get them from config/company_tables.php; existing company tables get them below.
     */
    private const SNAPSHOT_COLUMNS = ['technical_manager_full_name', 'technical_manager_mobile'];

    public function up(): void
    {
        Schema::create('technical_managers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('national_code', 20);
            $table->string('mobile', 20);
            $table->enum('status', StatusEnum::values())->default(StatusEnum::ACTIVE->value)->index();
            // حداکثر تعداد بارنامه‌ای که در یک روز با این مدیر فنی صادر می‌شود.
            $table->unsignedInteger('daily_limit')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'national_code']);
        });

        foreach ($this->existingWaybillTables() as $tableName) {
            $this->addWaybillColumns($tableName);
        }
    }

    public function down(): void
    {
        foreach ($this->existingWaybillTables() as $tableName) {
            if (Schema::hasColumn($tableName, 'technical_manager_id')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->dropForeign(['technical_manager_id']);
                    $table->dropIndex(['technical_manager_id']);
                    $table->dropColumn('technical_manager_id');
                });
            }

            $snapshotColumns = array_values(array_filter(
                self::SNAPSHOT_COLUMNS,
                fn(string $column): bool => Schema::hasColumn($tableName, $column),
            ));

            if ($snapshotColumns !== []) {
                Schema::table($tableName, fn(Blueprint $table) => $table->dropColumn($snapshotColumns));
            }
        }

        Schema::dropIfExists('technical_managers');
    }

    private function addWaybillColumns(string $tableName): void
    {
        if (!Schema::hasColumn($tableName, 'technical_manager_id')) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $column = $table->unsignedBigInteger('technical_manager_id')->nullable()->index();

                if (Schema::hasColumn($tableName, 'liability_insurance')) {
                    $column->after('liability_insurance');
                }

                $table->foreign('technical_manager_id')
                    ->references('id')
                    ->on('technical_managers')
                    ->nullOnDelete();
            });
        }

        foreach (self::SNAPSHOT_COLUMNS as $snapshotColumn) {
            if (Schema::hasColumn($tableName, $snapshotColumn)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($snapshotColumn, $tableName): void {
                $column = $table->string($snapshotColumn)->nullable();

                if (Schema::hasColumn($tableName, 'technical_manager_id')) {
                    $column->after($snapshotColumn === 'technical_manager_full_name'
                        ? 'technical_manager_id'
                        : 'technical_manager_full_name');
                }
            });
        }
    }

    /** @return list<string> */
    private function existingWaybillTables(): array
    {
        return DB::table('companies')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn($companyId): string => "company_{$companyId}_waybills")
            ->filter(fn(string $tableName): bool => Schema::hasTable($tableName))
            ->values()
            ->all();
    }
};
