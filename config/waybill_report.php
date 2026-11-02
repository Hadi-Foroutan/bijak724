<?php

return [
    // Exports are only ever built by a queue job, on one of two queues so that small exports are never stuck behind big ones:
    //  - light: up to `light_max_rows` waybills. A worker that stays running listens to it, so a job starts the moment it is queued.
    //  - heavy: more than that. A worker started by a cron job every minute (`queue:work --queue=<heavy> --stop-when-empty`) takes them.
    'queues' => [
        'light' => env('REPORT_QUEUE_LIGHT', 'reports-light'),
        'heavy' => env('REPORT_QUEUE_HEAVY', 'reports-heavy'),
    ],

    // Waybills up to this number make a light export (REPORT_LIGHT_MAX_ROWS in the .env).
    'light_max_rows' => (int)env('REPORT_LIGHT_MAX_ROWS', 2000),

    // Disk and folder of the generated files. Files are private and only reachable through a signed link.
    'disk' => env('REPORT_DISK', 'local'),
    'directory' => 'report-exports',

    // Put a .ttf/.otf file in one of these folders to change the font of the PDF and Excel files (the newest file wins;
    // none = the bundled PDF font and Excel's default). The first one is the same as the `local` disk's `report-fonts`.
    'pdf_font_directories' => array_filter([
        env('REPORT_PDF_FONT_DIRECTORY'),
        storage_path('app/private/report-fonts'),
        storage_path('app/report-fonts'),
    ]),

    // The font NAME written in Excel files (Excel cannot embed fonts: it must be installed on the computer that opens the
    // file). Empty = the family name read from the font file; set it when the installed font is listed under another name.
    'excel_font_name' => env('REPORT_EXCEL_FONT_NAME'),

    // How many rows are loaded from the database at a time.
    'chunk_size' => 500,

    // Rows per export, to keep a single job from exhausting memory or time.
    'max_rows' => [
        // Laravel Excel (PhpSpreadsheet) keeps the whole sheet in memory while writing.
        'xlsx' => 30000,
        'pdf' => 5000,
    ],

    // Queued or running exports a user may have at once.
    'max_active_per_user' => 5,

    // Seconds a job may run: a light export is small, a heavy one can be big. Keep both below DB_QUEUE_RETRY_AFTER (config/queue.php).
    'light_job_timeout' => 300,
    'job_timeout' => 1800,

    // Relations of the waybill an admin may read in a report field path. Anything else is rejected, so a field
    // can never reach into data the report builder was not meant to expose.
    'allowed_relations' => [
        'sender', 'receiver',
        'senderAddress', 'senderAddress.city', 'receiverAddress', 'receiverAddress.city',
        'firstDriver', 'secondDriver', 'referralDriver',
        'fleet', 'fleet.fleetType', 'fleet.fleetBrand', 'fleet.loadingType', 'fleet.driverLicenseType',
        'transportContract', 'insurance', 'insurance.insuranceCompany', 'technicalManager', 'creator',
        'cargos', 'cargos.cargo', 'cargos.packaging', 'cargos.productOwner',
    ],

    'formats' => ['text', 'number', 'bool', 'datetime', 'status', 'plate'],
];
