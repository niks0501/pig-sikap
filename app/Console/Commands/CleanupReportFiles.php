<?php

namespace App\Console\Commands;

use App\Models\GeneratedReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupReportFiles extends Command
{
    protected $signature = 'reports:cleanup {--days=90 : Delete report files older than this many days}';

    protected $description = 'Remove old generated report files from disk and mark records as archived.';

    public function handle(): int
    {
        $cutoff = now()->subDays((int) $this->option('days'));

        $reports = GeneratedReport::query()
            ->where('generated_at', '<', $cutoff)
            ->where('status', 'generated')
            ->get();

        if ($reports->isEmpty()) {
            $this->info('No old reports to clean up.');

            return self::SUCCESS;
        }

        $disk = Storage::disk('public');
        $deleted = 0;

        foreach ($reports as $report) {
            if ($report->file_path && $disk->exists($report->file_path)) {
                $disk->delete($report->file_path);
            }

            $report->update(['status' => 'archived']);
            $deleted++;
        }

        $this->info("Archived {$deleted} report(s) older than {$cutoff->format('M d, Y')}.");

        return self::SUCCESS;
    }
}
