<?php

namespace App\Console\Commands;

use App\Services\Company\Report\ReportExportService;
use Illuminate\Console\Command;

class PruneReportExportsCommand extends Command
{
    protected $signature = 'reports:prune';

    protected $description = 'Delete the report exports (file and record) whose retention time is over';

    public function handle(ReportExportService $reportExportService): int
    {
        $this->info("Deleted {$reportExportService->pruneExpired()} expired report export(s).");

        return self::SUCCESS;
    }
}
