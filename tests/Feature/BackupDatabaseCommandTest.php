<?php

use App\Services\Database\DatabaseDumper;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->backupPath = storage_path('framework/testing/backups');
    File::deleteDirectory($this->backupPath);
    config()->set('database_backup.path', $this->backupPath);
});

afterEach(function () {
    File::deleteDirectory($this->backupPath);
});

test('it rejects non mysql connections and creates no files', function () {
    $this->artisan('db:backup', ['--connection' => 'sqlite'])->assertFailed();

    expect(File::glob("{$this->backupPath}/*"))->toBeEmpty();
});

test('sql literals are escaped and typed correctly without any external tool', function () {
    $quote = fn(string $value): string => "'" . addslashes($value) . "'";

    expect(DatabaseDumper::literal(null, false, $quote))->toBe('NULL')
        ->and(DatabaseDumper::literal(15, false, $quote))->toBe('15')
        ->and(DatabaseDumper::literal(1.5, false, $quote))->toBe('1.5')
        ->and(DatabaseDumper::literal("it's", false, $quote))->toBe("'it\\'s'")
        ->and(DatabaseDumper::literal('تهران', false, $quote))->toBe("'تهران'")
        ->and(DatabaseDumper::literal("\x00\xFF", true, $quote))->toBe('0x00ff')
        ->and(DatabaseDumper::literal('', true, $quote))->toBe("''");
});
