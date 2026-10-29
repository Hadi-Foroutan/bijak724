<?php

namespace App\Listeners;

use App\Support\Logging\RunLogger;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Throwable;

/**
 * With CRON_LOGS=true every artisan command and queue job writes its start, end, duration and exact errors to its own
 * file (see RunLogger). Errors the application reports while a command or job runs (`report($exception)`, which does not
 * fail the run) are added to the file of that command/job too.
 */
class RunLogSubscriber
{
    private const ERRORS = ['error', 'critical', 'alert', 'emergency'];

    private ?string $command = null;

    private ?string $job = null;

    /** @var array<string, float> */
    private array $startedAt = [];

    public function __construct(private readonly RunLogger $logger)
    {
    }

    /** @return array<class-string, string> */
    public function subscribe(): array
    {
        return [
            CommandStarting::class => 'commandStarting',
            CommandFinished::class => 'commandFinished',
            JobProcessing::class => 'jobProcessing',
            JobProcessed::class => 'jobProcessed',
            JobExceptionOccurred::class => 'jobException',
            JobFailed::class => 'jobFailed',
            MessageLogged::class => 'messageLogged',
        ];
    }

    public function commandStarting(CommandStarting $event): void
    {
        if ($this->ignored($event->command)) {
            return;
        }

        $this->command = $event->command;
        $this->startedAt["command:{$event->command}"] = microtime(true);
        $this->logger->write('commands', $event->command, 'info', 'started', ['input' => (string)$event->input]);
    }

    public function commandFinished(CommandFinished $event): void
    {
        if ($this->ignored($event->command)) {
            return;
        }

        $this->logger->write(
            'commands',
            $event->command,
            $event->exitCode === 0 ? 'info' : 'error',
            $event->exitCode === 0 ? 'finished' : 'finished with an error',
            ['exit_code' => $event->exitCode, 'took' => $this->took("command:{$event->command}")],
        );
        $this->command = null;
    }

    public function jobProcessing(JobProcessing $event): void
    {
        $name = $this->jobName($event->job);
        $this->job = $name;
        $this->startedAt["job:{$event->job->getJobId()}"] = microtime(true);
        $this->logger->write('jobs', $name, 'info', 'started', [
            'job_id' => $event->job->getJobId(),
            'queue' => $event->job->getQueue(),
            'attempt' => $event->job->attempts(),
        ]);
    }

    public function jobProcessed(JobProcessed $event): void
    {
        $this->logger->write('jobs', $this->jobName($event->job), 'info', 'finished', [
            'job_id' => $event->job->getJobId(),
            'took' => $this->took("job:{$event->job->getJobId()}"),
        ]);
        $this->job = null;
    }

    public function jobException(JobExceptionOccurred $event): void
    {
        $this->logger->write('jobs', $this->jobName($event->job), 'error', 'threw an exception (it may be retried)', [
            'job_id' => $event->job->getJobId(),
            'attempt' => $event->job->attempts(),
            'took' => $this->took("job:{$event->job->getJobId()}"),
            'exception' => $event->exception,
        ]);
    }

    public function jobFailed(JobFailed $event): void
    {
        $this->logger->write('jobs', $this->jobName($event->job), 'error', 'failed', [
            'job_id' => $event->job->getJobId(),
            'attempt' => $event->job->attempts(),
            'exception' => $event->exception,
        ]);
        $this->job = null;
    }

    /** An exception the application reported (and survived) while a job or command was running. */
    public function messageLogged(MessageLogged $event): void
    {
        if ($this->logger->isWriting() || !in_array($event->level, self::ERRORS, true)) {
            return;
        }

        $context = ['exception' => $event->context['exception'] ?? null];
        $context = array_filter($context, fn(mixed $value): bool => $value instanceof Throwable);

        if ($this->job !== null) {
            $this->logger->write('jobs', $this->job, 'error', 'reported: ' . $event->message, $context);
        } elseif ($this->command !== null) {
            $this->logger->write('commands', $this->command, 'error', 'reported: ' . $event->message, $context);
        }
    }

    private function ignored(string $command): bool
    {
        return in_array($command, (array)config('cron_logs.ignored_commands'), true);
    }

    private function jobName(object $job): string
    {
        return class_basename((string)$job->resolveName());
    }

    private function took(string $key): string
    {
        $started = $this->startedAt[$key] ?? null;
        unset($this->startedAt[$key]);

        return $started === null ? '-' : round(microtime(true) - $started, 2) . 's';
    }
}
