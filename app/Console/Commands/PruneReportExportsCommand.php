<?php

namespace App\Console\Commands;

use App\Services\Company\Report\ReportExportService;
use Illuminate\Console\Command;

class PruneReportExportsCommand extends Command
{
    protected $signature = 'reports:prune';

    protected $description = 'Delete the files of report exports whose retention time is over and mark them expired';

    public function handle(ReportExportService $reportExportService): int
    {
        $this->info("Expired {$reportExportService->pruneExpired()} report export(s).");

        return self::SUCCESS;
    }
}
