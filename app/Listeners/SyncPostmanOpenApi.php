<?php

namespace App\Listeners;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\File;

/**
 * بعد از هر `php artisan scramble:export` همان خروجی را در فایل OpenAPI مخصوص Postman هم می‌نویسد
 * (پیش‌فرض: storage/app/postman-openapi.json). با خالی گذاشتن scramble.postman_export_path غیرفعال می‌شود.
 */
class SyncPostmanOpenApi
{
    public function handle(CommandFinished $event): void
    {
        if ($event->command !== 'scramble:export' || $event->exitCode !== 0) {
            return;
        }

        $target = config('scramble.postman_export_path');

        if (blank($target)) {
            return;
        }

        $api = $event->input->hasOption('api') ? (string)$event->input->getOption('api') : 'default';
        $source = $event->input->hasOption('path') ? $event->input->getOption('path') : null;
        $source = $source ?: config('scramble.export_path') ?: 'api' . ($api === 'default' ? '' : "-{$api}") . '.json';

        // اگر کاربر خروجی را مستقیم روی همین فایل گرفته باشد کاری لازم نیست.
        if (!File::exists($source) || realpath($source) === (realpath($target) ?: null)) {
            return;
        }

        File::ensureDirectoryExists(dirname($target));
        File::copy($source, $target);

        $event->output->writeln("<info>Postman OpenAPI file updated: {$target}</info>");
    }
}
