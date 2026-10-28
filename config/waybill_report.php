<?php

return [
    // Exports run on their own queue so one worker (`queue:work --queue=reports`) handles them one after another.
    'queue' => env('REPORT_QUEUE', 'reports'),

    // Disk and folder of the generated files. Files are private and only reachable through a signed link.
    'disk' => env('REPORT_DISK', 'local'),
    'directory' => 'report-exports',

    // Put a .ttf/.otf file here to change the font of the PDF files (the newest file wins; empty = the bundled font).
    'pdf_font_directory' => env('REPORT_PDF_FONT_DIRECTORY', storage_path('app/report-fonts')),

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
