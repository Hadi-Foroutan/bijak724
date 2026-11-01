<?php

namespace App\Support\Reverb;

use App\Models\User;
use App\Support\Logging\SocketLogger;
use Laravel\Reverb\Contracts\Connection;
use Laravel\Reverb\Protocols\Pusher\Contracts\ChannelManager;
use Laravel\Reverb\Protocols\Pusher\EventHandler;
use Laravel\Reverb\Protocols\Pusher\Server;
use Throwable;

/**
 * Reverb's own Pusher server that also prints who connects, to the terminal of `php artisan reverb:start` (and, with
 * SOCKET_LOGS=true, to storage/logs/socket_logs.log): the socket id when a client connects, the user when it subscribes to its private channel, and who
 * left when it disconnects. Reverb itself only prints this with `--debug`, and never names the user.
 */
class LoggingPusherServer extends Server
{
    /** The channel of a user's reports, see routes/channels.php. */
    private const USER_CHANNEL = '/^private-users\.(\d+)\./';

    /** @var array<string, array<int, string>> socket id => user id => label, while connected */
    private array $users = [];

    private int $connected = 0;

    public function __construct(ChannelManager $channels, EventHandler $handler, protected SocketLogger $logger)
    {
        parent::__construct($channels, $handler);

        $this->write('server started', null, sprintf(
            'pid %d · %s:%s',
            getmypid(),
            config('reverb.servers.reverb.host'),
            config('reverb.servers.reverb.port'),
        ));
    }

    /** A connection failed (bad message, broken handshake, ...). */
    public function error(Connection $connection, Throwable $exception): void
    {
        parent::error($connection, $exception);

        $this->write('error', $connection, get_class($exception) . ': ' . $exception->getMessage(), 'error');
    }

    public function open(Connection $connection): void
    {
        parent::open($connection);

        $this->connected++;
        $this->write('connected', $connection, 'origin: ' . ($connection->origin() ?? '-'));
    }

    public function message(Connection $from, string $message): void
    {
        parent::message($from, $message);

        $event = json_decode($message, true);

        if (!is_array($event) || ($event['event'] ?? null) !== 'pusher:subscribe') {
            return;
        }

        $data = is_string($event['data'] ?? null) ? json_decode($event['data'], true) : ($event['data'] ?? []);
        $channel = is_array($data) ? (string)($data['channel'] ?? '') : '';

        // The subscription may have been refused (bad auth); only a channel the connection really joined is reported.
        if ($channel === '') {
            return;
        }

        if (!$this->channels->for($from->app())->find($channel)?->subscribed($from)) {
            $this->write('subscription refused', $from, "channel: {$channel}", 'warning');

            return;
        }

        if (preg_match(self::USER_CHANNEL, $channel, $matches) === 1) {
            $label = $this->userLabel((int)$matches[1]);
            $this->users[$from->id()][(int)$matches[1]] = $label;
            $this->write('user subscribed', $from, "{$label} · channel: {$channel}");

            return;
        }

        $this->write('subscribed', $from, "channel: {$channel}");
    }

    public function close(Connection $connection): void
    {
        parent::close($connection);

        $this->connected = max($this->connected - 1, 0);
        $who = implode(', ', $this->users[$connection->id()] ?? []);
        unset($this->users[$connection->id()]);

        $this->write('disconnected', $connection, $who === '' ? 'no user' : $who);
    }

    private function userLabel(int $id): string
    {
        try {
            $user = User::query()->find($id, ['id', 'full_name', 'company_id']);
        } catch (Throwable) {
            $user = null;
        }

        return $user === null
            ? "user #{$id}"
            : "user #{$user->id} ({$user->full_name}) company #{$user->company_id}";
    }

    /** One line to the terminal and, when SOCKET_LOGS is on, to the socket log. */
    private function write(string $what, ?Connection $connection, string $details, string $level = 'info'): void
    {
        $line = sprintf(
            '[%s] %s%s · %s · online: %d',
            now()->format('Y-m-d H:i:s'),
            $what,
            $connection === null ? '' : " · socket {$connection->id()}",
            $details,
            $this->connected,
        );

        fwrite(STDOUT, $line . PHP_EOL);
        $this->logger->write($line, $level);
    }
}
