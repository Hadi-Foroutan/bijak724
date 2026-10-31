<?php

/*
| Ready-made ("frequently used") reports. All of them share one fixed layout (company heading, period line, table, totals row)
| and the same export pipeline (queue, socket, PDF/Excel, temporary link); a report type only lists its fields.
|
| Where things live (App\Services\Company\Report\Templates):
|   this file                 which types exist and what each one shows
|   TemplateFieldLibrary      the fields (columns) a type can list
|   TemplateFilterLibrary     the filters (period) a type can accept from the frontend
|   ReportTemplateRegistry    reads this file into ReportTemplate objects
|   TemplateDocumentBuilder   builds the file content: heading, period, rows, totals
|   Layouts\*                 how rows are made: PerWaybillRecords | PerGroupRecords | SummaryRecords
|   FrequentReportController  the API (list + export), validated by StoreFrequentReportExportRequest
|
| To add a type: add an entry here. `columns` is the list of field names in the order they appear (first = rightmost); the
| names are defined in App\Services\Company\Report\Templates\TemplateFieldLibrary. A field can be renamed or resized in one
| report only: ['field' => 'cargo_value', 'label' => 'ارزش کالا', 'width' => 90].
| A field that does not exist yet is added once in TemplateFieldLibrary and is then available to every type.
|
|   title        the kind of report, written before the company name in the heading
|   orientation  auto | portrait | landscape (PDF page); auto = landscape when more than 6 columns are printed
|   constraints  always applied to the waybills of this type by the backend (has_insurance, sort_by, sort_direction ...)
|   filters      what the frontend may send for this type, by name from App\Services\Company\Report\Templates\TemplateFilterLibrary
|                (issued_from, issued_to); a filter can be made optional or renamed in one type: ['filter' => 'issued_to', 'required' => false]
|   layout       table (default) | summary (every column is one number for the period, shown as label/value pairs)
|   group_by     optional: a field name; one row per value of it (columns then use waybill_count / weight_sum / weight_avg)
|   columns      field names
*/
return [
    'types' => [
        'insurance' => [
            'title' => 'گزارش بیمه',
            'orientation' => 'auto',
            'constraints' => ['has_insurance' => true, 'sort_by' => 'issued_at', 'sort_direction' => 'asc'],
            'filters' => ['issued_from', 'issued_to'],
            'columns' => [
                'row', 'date', 'bijak_number', 'serial', 'destination', 'cargo', 'plate', 'insurance',
                'cargo_value', 'premium', 'vat',
            ],
        ],

        'loader-insurance' => [
            'title' => 'گزارش بیمه بارگیر',
            'orientation' => 'auto',
            'constraints' => ['has_insurance' => true, 'sort_by' => 'issued_at', 'sort_direction' => 'asc'],
            'filters' => ['issued_from', 'issued_to'],
            'columns' => [
                'row', 'date', 'bijak_number', 'serial', 'destination', 'cargo', 'loading_type', 'plate', 'insurance',
                'cargo_value', 'premium', 'vat',
            ],
        ],

        'loader-insurance-cargo-value' => [
            'title' => 'گزارش بیمه بارگیر و ارزش محموله',
            'orientation' => 'auto',
            'constraints' => ['has_insurance' => true, 'sort_by' => 'issued_at', 'sort_direction' => 'asc'],
            'filters' => ['issued_from', 'issued_to'],
            'columns' => [
                'row', 'date', 'bijak_number', 'serial', 'destination', 'cargo', 'loading_type', 'plate', 'insurance',
                'cargo_value', 'premium', 'vat',
            ],
        ],

        // One row per truck type instead of one row per waybill.
        'loader' => [
            'title' => 'گزارش بارگیر',
            'orientation' => 'auto',
            'group_by' => 'loading_type',
            'filters' => ['issued_from', 'issued_to'],
            'columns' => [
                'row',
                ['field' => 'loading_type', 'label' => 'نوع کامیون', 'width' => 200],
                'waybill_count', 'weight_sum', 'weight_avg',
            ],
        ],

        // A summary of the period: one number per metric (label/value pairs), not one row per waybill.
        'accounting-summary' => [
            'title' => 'خلاصه گزارش حسابداری',
            'layout' => 'summary',
            'orientation' => 'auto',
            'filters' => ['issued_from', 'issued_to'],
            'columns' => [
                'count_all', 'count_valid', 'count_canceled',
                'total_commission', 'total_base_freight', 'total_loading', 'total_weighbridge',
                'total_premium', 'total_vat', 'total_premium_vat', 'total_cargo_value', 'total_origin_weight',
                'total_driver_receivable',
            ],
        ],
    ],
];
