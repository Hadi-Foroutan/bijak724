<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait HasAutoIncrement
{
    protected function setStartId(string $table, int $start = 100): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = {$start}");
    }
}
