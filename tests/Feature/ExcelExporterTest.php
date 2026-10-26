<?php

use App\Enums\WaybillStatus;
use App\Support\Excel\ExcelDesign;
use App\Support\Excel\ExcelExporter;
use App\Support\Excel\StripedExcelDesign;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

beforeEach(function (): void {
    Storage::fake('local');

    $this->sheetOf = function (string $path): Worksheet {
        return IOFactory::load(Storage::disk('local')->path($path))->getActiveSheet();
    };
});

test('headings are detected from the first row and values are handled the same way for any data', function () {
    $rows = (function () {
        yield ['name' => 'علی', 'mobile' => '09121234567', 'amount' => 1500000, 'active' => true, 'status' => WaybillStatus::Completed,
            'at' => Carbon::parse('2026-10-01 08:30:00'), 'tags' => ['الف', 'ب'], 'note' => null,
            'owner' => ['id' => 4, 'title' => 'شرکت']];
        yield ['name' => 'رضا', 'mobile' => '09350000000', 'amount' => 20, 'active' => false, 'status' => WaybillStatus::Canceled,
            'at' => null, 'tags' => [], 'note' => 'ok', 'owner' => ['id' => 5, 'title' => 'x']];
    })();

    app(ExcelExporter::class)->store($rows, 'e/auto.xlsx', 'local');
    $sheet = ($this->sheetOf)('e/auto.xlsx');

    expect($sheet->rangeToArray('A1:J1')[0])
        ->toBe(['name', 'mobile', 'amount', 'active', 'status', 'at', 'tags', 'note', 'owner.id', 'owner.title'])
        ->and($sheet->getCell('B2')->getValue())->toBe('09121234567')   // صفر اول حفظ می‌شود
        ->and($sheet->getCell('C2')->getValue())->toEqual(1500000)       // عدد، عدد می‌ماند
        ->and($sheet->getCell('D2')->getValue())->toBe('بله')
        ->and($sheet->getCell('E2')->getValue())->toBe(WaybillStatus::Completed->label())
        ->and($sheet->getCell('F2')->getValue())->toBe('2026-10-01 08:30:00')
        ->and($sheet->getCell('G2')->getValue())->toBe('الف، ب')
        ->and($sheet->getCell('I2')->getValue())->toEqual(4)
        ->and($sheet->getCell('D3')->getValue())->toBe('خیر');
});

test('columns can be a key => heading map and models or objects work as rows', function () {
    $rows = [
        (object)['full_name' => 'علی', 'phone' => '0912', 'secret' => 'x'],
        collect(['full_name' => 'رضا', 'phone' => '0935', 'secret' => 'y']),
    ];

    app(ExcelExporter::class)->store($rows, 'e/map.xlsx', 'local', ['full_name' => 'نام', 'phone' => 'موبایل']);
    $sheet = ($this->sheetOf)('e/map.xlsx');

    expect($sheet->rangeToArray('A1:B3'))->toBe([['نام', 'موبایل'], ['علی', '0912'], ['رضا', '0935']])
        ->and($sheet->getHighestColumn())->toBe('B');
});

test('the same data can be given any design, by class name or instance', function () {
    $rows = [['a' => 1], ['a' => 2], ['a' => 3]];

    app(ExcelExporter::class)->store($rows, 'e/default.xlsx', 'local');
    app(ExcelExporter::class)->store($rows, 'e/striped.xlsx', 'local', design: StripedExcelDesign::class);
    app(ExcelExporter::class)->store($rows, 'e/custom.xlsx', 'local', design: new class implements ExcelDesign {
        public function apply(Worksheet $sheet, int $columns, int $rows): void
        {
            $sheet->setCellValue('C1', "{$columns}x{$rows}");
        }
    });

    $default = ($this->sheetOf)('e/default.xlsx');
    $striped = ($this->sheetOf)('e/striped.xlsx');

    expect($default->getRightToLeft())->toBeTrue()
        ->and($default->getStyle('A1')->getFill()->getStartColor()->getRGB())->toBe('E8E8E8')
        ->and($striped->getStyle('A1')->getFill()->getStartColor()->getRGB())->toBe('1F4E78')
        ->and($striped->getStyle('A3')->getFill()->getStartColor()->getRGB())->toBe('F2F2F2')
        ->and(($this->sheetOf)('e/custom.xlsx')->getCell('C1')->getValue())->toBe('1x4');
});

test('an empty list still produces a valid file', function () {
    app(ExcelExporter::class)->store([], 'e/empty.xlsx', 'local', ['a' => 'الف']);

    expect(($this->sheetOf)('e/empty.xlsx')->getCell('A1')->getValue())->toBe('الف');
});
