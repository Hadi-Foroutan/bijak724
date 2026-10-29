<?php

return [
    // CRON_LOGS=true in the .env: every artisan command and every queue job writes what it did (and the exact errors)
    // to its own file. Off by default. After changing the .env run `php artisan optimize:clear` (or deploy) so the
    // cached config sees it.
    'enabled' => (bool)env('CRON_LOGS', false),

    // Folder of the logs: <path>/commands/<command>-<date>.log and <path>/jobs/<Job>-<date>.log.
    'path' => storage_path('logs'),

    // How many daily files are kept per command/job.
    'days' => 14,

    // Commands that run all the time or every minute and would only add noise.
    'ignored_commands' => [
        'schedule:run', 'schedule:work', 'schedule:finish', 'queue:work', 'queue:listen', 'queue:restart',
        'reverb:start', 'reverb:restart', 'list', 'help', 'tinker', 'about', 'env', 'serve', 'package:discover',
    ],
];
