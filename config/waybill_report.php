<?php

return [
    // Exports run on their own queue so one worker (`queue:work --queue=reports`) handles them one after another.
    'queue' => env('REPORT_QUEUE', 'reports'),

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
