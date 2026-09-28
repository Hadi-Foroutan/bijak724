<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->companyShipmentPartyTables() as $tableName) {
            if (! Schema::hasColumn($tableName, 'full_name')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->string('full_name')->nullable()->after('last_name');
                });
            }

            DB::table($tableName)
                ->select(['id', 'first_name', 'last_name'])
                ->orderBy('id')
                ->chunkById(500, function ($shipmentParties) use ($tableName): void {
                    foreach ($shipmentParties as $shipmentParty) {
                        $fullName = Str::squish(
                            (string) $shipmentParty->first_name.' '.(string) $shipmentParty->last_name,
                        );

                        DB::table($tableName)
                            ->where('id', $shipmentParty->id)
                            ->update(['full_name' => $fullName === '' ? null : $fullName]);
                    }
                });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->companyShipmentPartyTables() as $tableName) {
            if (Schema::hasColumn($tableName, 'full_name')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->dropColumn('full_name');
                });
            }
        }
    }

    /** @return list<string> */
    private function companyShipmentPartyTables(): array
    {
        return collect(Schema::getTables())
            ->pluck('name')
            ->filter(fn (string $tableName): bool => preg_match(
                '/^company_\d+_shipment_parties$/',
                $tableName,
            ) === 1)
            ->values()
            ->all();
    }
};
