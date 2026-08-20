<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class RefreshTable extends Command
{
    protected $signature = 'table:refresh {table}';
    protected $description = 'Refresh a specific table (rollback + migrate)';

    public function handle()
    {
        $table = $this->argument('table');

        $migration = collect(File::files(database_path('migrations')))
            ->first(fn ($file) => str_contains($file->getFilename(), $table));

        if (!$migration) {
            $this->error("Migration for table [$table] not found.");
            return;
        }

        $filename = $migration->getFilename();
        $migrationName = str_replace('.php', '', $filename);

        $path = 'database/migrations/' . $filename;

        $this->info("Dropping table: $table");

        if (Schema::hasTable($table)) {
            Schema::drop($table);
        }

        // 🔥 حذف از جدول migrations
        DB::table('migrations')->where('migration', $migrationName)->delete();

        $this->info("Migrating: $path");

        Artisan::call('migrate', [
            '--path' => $path,
            '--force' => true,
        ]);

        $this->info("Table [$table] refreshed successfully ✅");
    }
}
