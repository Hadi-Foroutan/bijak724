<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ResetDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-database
                            {--force : Force the operation to run in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild, seed, and initialize the application database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $exitCode = $this->call('migrate:fresh', [
            '--seed' => true,
            '--force' => (bool) $this->option('force'),
        ]);

        if ($exitCode !== self::SUCCESS) {
            return $exitCode;
        }

        foreach (config('database_reset.commands', []) as $command => $arguments) {
            $this->newLine();
            $this->info("Running [{$command}]...");

            $exitCode = $this->call($command, $arguments);

            if ($exitCode !== self::SUCCESS) {
                $this->error("Command [{$command}] failed.");

                return $exitCode;
            }
        }

        $this->newLine();
        $this->info('Database reset completed successfully.');

        return self::SUCCESS;
    }
}
