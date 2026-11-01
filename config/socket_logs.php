<?php

return [
    // SOCKET_LOGS=true in the .env: the Reverb (websocket) server writes who connects, subscribes and leaves, its start and
    // its errors to storage/logs/socket_logs.log (the same lines `php artisan reverb:start` prints in the terminal).
    // Off by default. After changing the .env run `php artisan optimize:clear` (or deploy) and restart Reverb
    // (`php artisan reverb:restart`) so the running server reads it.
    'enabled' => (bool)env('SOCKET_LOGS', false),

    // The file of the log (one file, appended to; delete or empty it when it gets big).
    'file' => storage_path('logs/socket_logs.log'),
];
