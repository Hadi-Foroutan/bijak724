<?php

namespace App\Support\Logging;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Writes the log of one artisan command or one queue job to its own daily file
 * (storage/logs/commands/reports-prune-2026-10-29.log, storage/logs/jobs/GenerateReportExport-2026-10-29.log).
 * Only when CRON_LOGS=true (config/cron_logs.php). Logging can never break the command or job it describes.
 */
class RunLogger
{
    /** @var array<string, LoggerInterface> */
    private array $loggers = [];

    private bool $writing = false;

    public function enabled(): bool
    {
        return (bool)config('cron_logs.enabled');
    }

    /** True while this class is writing: the log it writes must not be handled as an application error again. */
    public function isWriting(): bool
    {
        return $this->writing;
    }

    /**
     * @param 'commands'|'jobs' $folder
     * @param array<string, mixed> $context
     */
    public function write(string $folder, string $name, string $level, string $message, array $context = []): void
    {
        if (!$this->enabled() || $this->writing) {
            return;
        }

        $this->writing = true;

        try {
            $this->logger($folder, $name)->log($level, $message, $context);
        } catch (Throwable) {
            // Never let logging break the run it describes.
        } finally {
            $this->writing = false;
        }
    }

    private function logger(string $folder, string $name): LoggerInterface
    {
        $file = trim((string)preg_replace('/[^A-Za-z0-9_.-]+/', '-', $name), '-') ?: 'unnamed';

        return $this->loggers["{$folder}/{$file}"] ??= Log::build([
            'driver' => 'daily',
            'path' => rtrim((string)config('cron_logs.path'), '/\\') . "/{$folder}/{$file}.log",
            'level' => 'debug',
            'days' => (int)config('cron_logs.days'),
        ]);
    }
}
