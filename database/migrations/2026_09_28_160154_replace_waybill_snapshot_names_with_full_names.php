<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const SNAPSHOT_NAMES = [
        'sender_full_name' => ['sender_first_name', 'sender_last_name'],
        'receiver_full_name' => ['receiver_first_name', 'receiver_last_name'],
        'driver1_full_name' => ['driver1_first_name', 'driver1_last_name'],
        'driver2_full_name' => ['driver2_first_name', 'driver2_last_name'],
        'referral_driver_full_name' => ['referral_driver_first_name', 'referral_driver_last_name'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->companyWaybillTables() as $tableName) {
            $columnsToAdd = collect(array_keys(self::SNAPSHOT_NAMES))
                ->reject(fn (string $column): bool => Schema::hasColumn($tableName, $column))
                ->values()
                ->all();

            if ($columnsToAdd !== []) {
                Schema::table($tableName, function (Blueprint $table) use ($columnsToAdd): void {
                    foreach ($columnsToAdd as $column) {
                        $table->string($column)->nullable();
                    }
                });
            }

            $sourceColumns = collect(self::SNAPSHOT_NAMES)
                ->flatten()
                ->unique()
                ->filter(fn (string $column): bool => Schema::hasColumn($tableName, $column))
                ->values()
                ->all();

            if ($sourceColumns !== []) {
                DB::table($tableName)
                    ->select(['id', ...$sourceColumns])
                    ->orderBy('id')
                    ->chunkById(500, function ($waybills) use ($tableName): void {
                        foreach ($waybills as $waybill) {
                            $updates = [];

                            foreach (self::SNAPSHOT_NAMES as $fullNameColumn => [$firstNameColumn, $lastNameColumn]) {
                                if (
                                    ! property_exists($waybill, $firstNameColumn)
                                    && ! property_exists($waybill, $lastNameColumn)
                                ) {
                                    continue;
                                }

                                $firstName = property_exists($waybill, $firstNameColumn)
                                    ? (string) $waybill->{$firstNameColumn}
                                    : '';
                                $lastName = property_exists($waybill, $lastNameColumn)
                                    ? (string) $waybill->{$lastNameColumn}
                                    : '';
                                $fullName = Str::squish("{$firstName} {$lastName}");

                                $updates[$fullNameColumn] = $fullName === '' ? null : $fullName;
                            }

                            DB::table($tableName)->where('id', $waybill->id)->update($updates);
                        }
                    });
            }

            if ($sourceColumns !== []) {
                Schema::table($tableName, function (Blueprint $table) use ($sourceColumns): void {
                    $table->dropColumn($sourceColumns);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->companyWaybillTables() as $tableName) {
            $columnsToRestore = collect(self::SNAPSHOT_NAMES)
                ->flatten()
                ->unique()
                ->reject(fn (string $column): bool => Schema::hasColumn($tableName, $column))
                ->values()
                ->all();

            if ($columnsToRestore !== []) {
                Schema::table($tableName, function (Blueprint $table) use ($columnsToRestore): void {
                    foreach ($columnsToRestore as $column) {
                        $table->string($column)->nullable();
                    }
                });
            }

            $fullNameColumns = collect(array_keys(self::SNAPSHOT_NAMES))
                ->filter(fn (string $column): bool => Schema::hasColumn($tableName, $column))
                ->values()
                ->all();

            if ($fullNameColumns !== []) {
                DB::table($tableName)
                    ->select(['id', ...$fullNameColumns])
                    ->orderBy('id')
                    ->chunkById(500, function ($waybills) use ($tableName): void {
                        foreach ($waybills as $waybill) {
                            $updates = [];

                            foreach (self::SNAPSHOT_NAMES as $fullNameColumn => [$firstNameColumn, $lastNameColumn]) {
                                if (! property_exists($waybill, $fullNameColumn)) {
                                    continue;
                                }

                                $fullName = Str::squish((string) $waybill->{$fullNameColumn});
                                $nameParts = $fullName === '' ? [] : explode(' ', $fullName, 2);

                                $updates[$firstNameColumn] = $nameParts[0] ?? null;
                                $updates[$lastNameColumn] = $nameParts[1] ?? null;
                            }

                            DB::table($tableName)->where('id', $waybill->id)->update($updates);
                        }
                    });

                Schema::table($tableName, function (Blueprint $table) use ($fullNameColumns): void {
                    $table->dropColumn($fullNameColumns);
                });
            }
        }
    }

    /** @return list<string> */
    private function companyWaybillTables(): array
    {
        return collect(Schema::getTables())
            ->pluck('name')
            ->filter(fn (string $tableName): bool => preg_match(
                '/^company_\d+_waybills$/',
                $tableName,
            ) === 1)
            ->values()
            ->all();
    }
};
