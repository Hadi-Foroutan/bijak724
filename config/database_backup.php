<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backup Directory
    |--------------------------------------------------------------------------
    |
    | Where `db:backup` writes its gzipped dumps. The default lives under
    | storage/app/private, which is git-ignored and not publicly served.
    |
    */

    'path' => env('DB_BACKUP_PATH', storage_path('app/private/backups/database')),

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | Backups older than this many days are deleted after each successful
    | backup. Set to 0 to keep every backup.
    |
    */

    'keep_days' => (int)env('DB_BACKUP_KEEP_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Schedule
    |--------------------------------------------------------------------------
    |
    | Time of day (24h, app timezone) the daily backup runs.
    |
    */

    'daily_at' => env('DB_BACKUP_DAILY_AT', '02:00'),

];
