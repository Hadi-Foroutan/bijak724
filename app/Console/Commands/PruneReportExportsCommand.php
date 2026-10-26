<?php

namespace App\Console\Commands;

use App\Enums\ReportExportStatus;
use App\Models\ReportExport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneReportExportsCommand extends Command
{
    protected $signature = 'reports:prune';

    protected $description = 'Delete the files of report exports whose retention time is over and mark them expired';

    public function handle(): int
    {
        $disk = Storage::disk(config('waybill_report.disk'));
        $pruned = 0;

        ReportExport::query()
            ->where('status', ReportExportStatus::Completed)
            ->where('expires_at', '<=', now())
            ->each(function (ReportExport $export) use ($disk, &$pruned): void {
                if ($export->file_path !== null) {
                    $disk->delete($export->file_path);
                }

                $export->update(['status' => ReportExportStatus::Expired, 'file_path' => null]);
                $pruned++;
            });

        $this->info("Expired {$pruned} report export(s).");

        return self::SUCCESS;
    }
}
