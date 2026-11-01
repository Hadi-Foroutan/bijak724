<?php

namespace App\Support\Logging;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Writes the lines of the Reverb (websocket) server to storage/logs/socket_logs.log, only when SOCKET_LOGS=true
 * (config/socket_logs.php). Logging can never break the socket server.
 */
class SocketLogger
{
    private ?LoggerInterface $logger = null;

    public function enabled(): bool
    {
        return (bool)config('socket_logs.enabled');
    }

    public function write(string $line, string $level = 'info'): void
    {
        if (!$this->enabled()) {
            return;
        }

        try {
            $this->logger ??= Log::build([
                'driver' => 'single',
                'path' => (string)config('socket_logs.file'),
                'level' => 'debug',
                // The line already starts with its own date.
                'formatter' => \Monolog\Formatter\LineFormatter::class,
                'formatter_with' => ['format' => "%message%\n", 'allowInlineLineBreaks' => false, 'ignoreEmptyContextAndExtra' => true],
            ]);

            $this->logger->log($level, $line);
        } catch (Throwable) {
            // Never let logging break the socket server.
        }
    }
}
