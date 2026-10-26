<?php

namespace Database\Seeders;

use App\Models\ReportField;
use App\Services\Company\Report\ReportFieldDefaults;
use Illuminate\Database\Seeder;

/** Adds the default report fields that are missing; fields an admin edited or deactivated are left alone. */
class ReportFieldSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ReportFieldDefaults::all() as $index => $field) {
            ReportField::query()->firstOrCreate(['key' => $field['key']], [...$field, 'sort_order' => $index]);
        }
    }
}
