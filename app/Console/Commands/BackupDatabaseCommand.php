<?php

namespace App\Console\Commands;

use App\Services\Database\DatabaseDumper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Throwable;

class BackupDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup
                            {--connection= : Database connection to back up (defaults to the default connection)}
                            {--keep= : Delete backups older than this many days (0 keeps all)}
                            {--no-compress : Write a plain .sql file instead of a gzipped .sql.gz}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Back up the database into a gzipped SQL file (pure PHP, no external tools) and prune old backups';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $connectionName = $this->option('connection') ?: config('database.default');
        $connection = config("database.connections.{$connectionName}");

        if (!is_array($connection) || !in_array($connection['driver'] ?? null, ['mysql', 'mariadb'], true)) {
            $this->error("Connection [{$connectionName}] is not a MySQL/MariaDB connection.");

            return self::FAILURE;
        }

        $directory = config('database_backup.path');
        File::ensureDirectoryExists($directory);

        $compress = !$this->option('no-compress');
        $path = sprintf(
            '%s/%s_%s.sql%s',
            $directory,
            $connection['database'],
            now()->format('Y-m-d_His'),
            $compress ? '.gz' : '',
        );

        try {
            $stats = (new DatabaseDumper(DB::connection($connectionName)))->dumpToFile($path, $compress);
        } catch (Throwable $exception) {
            File::delete($path);
            $this->error("Database backup failed: {$exception->getMessage()}");

            return self::FAILURE;
        }

        $this->line("Dumped {$stats['tables']} tables, {$stats['views']} views and {$stats['rows']} rows.");
        $this->info("Database backed up to [{$path}].");

        $keepDays = (int)($this->option('keep') ?? config('database_backup.keep_days'));

        if ($keepDays > 0) {
            foreach ($this->pruneOlderThan($directory, $keepDays) as $file) {
                $this->line("Deleted old backup [{$file}].");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Delete backups (.sql and .sql.gz) older than the given number of days.
     *
     * @return array<int, string> the deleted file names
     */
    private function pruneOlderThan(string $directory, int $days): array
    {
        $threshold = now()->subDays($days)->getTimestamp();

        return collect([...File::glob("{$directory}/*.sql.gz"), ...File::glob("{$directory}/*.sql")])
            ->filter(fn(string $file): bool => File::lastModified($file) < $threshold)
            ->each(fn(string $file) => File::delete($file))
            ->map(fn(string $file): string => basename($file))
            ->values()
            ->all();
    }
}
