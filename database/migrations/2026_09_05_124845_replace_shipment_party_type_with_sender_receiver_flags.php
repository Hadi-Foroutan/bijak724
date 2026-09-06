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
        foreach ($this->shipmentPartyTables() as $tableName) {
            $hasSender = Schema::hasColumn($tableName, 'is_sender');
            $hasReceiver = Schema::hasColumn($tableName, 'is_receiver');

            if (! $hasSender || ! $hasReceiver) {
                Schema::table($tableName, function (Blueprint $table) use ($hasSender, $hasReceiver): void {
                    if (! $hasSender) {
                        $table->boolean('is_sender')->default(false);
                    }

                    if (! $hasReceiver) {
                        $table->boolean('is_receiver')->default(false);
                    }
                });
            }

            if (! Schema::hasColumn($tableName, 'type')) {
                continue;
            }

            DB::table($tableName)
                ->whereIn('type', ['sender', 'both'])
                ->update(['is_sender' => true]);
            DB::table($tableName)
                ->whereIn('type', ['receiver', 'both'])
                ->update(['is_receiver' => true]);

            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->shipmentPartyTables() as $tableName) {
            if (! Schema::hasColumn($tableName, 'type')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->enum('type', ['sender', 'receiver', 'both'])->nullable();
                });
            }

            DB::table($tableName)->update([
                'type' => DB::raw("CASE
                    WHEN is_sender = 1 AND is_receiver = 1 THEN 'both'
                    WHEN is_receiver = 1 THEN 'receiver'
                    ELSE 'sender'
                END"),
            ]);

            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn(['is_sender', 'is_receiver']);
            });
        }
    }

    /** @return list<string> */
    private function shipmentPartyTables(): array
    {
        return collect(Schema::getTableListing())
            ->map(fn (string $tableName): string => collect(explode('.', $tableName))->last())
            ->filter(fn (string $tableName): bool => preg_match('/^company_\d+_shipment_parties$/', $tableName) === 1)
            ->values()
            ->all();
    }
};
