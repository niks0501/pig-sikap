<?php

namespace App\Console\Commands;

use App\Models\GeneratedReport;
use App\Models\ReportSchedule;
use App\Services\PigRegistry\ReportCsvService;
use App\Services\PigRegistry\ReportFilterService;
use App\Services\PigRegistry\ReportPdfService;
use App\Services\PigRegistry\Reports\ExpenseReportService;
use App\Services\PigRegistry\Reports\HealthReportService;
use App\Services\PigRegistry\Reports\InventoryReportService;
use App\Services\PigRegistry\Reports\MonthlyReportService;
use App\Services\PigRegistry\Reports\MortalityReportService;
use App\Services\PigRegistry\Reports\ProfitabilityReportService;
use App\Services\PigRegistry\Reports\QuarterlyReportService;
use App\Services\PigRegistry\Reports\SalesReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunScheduledReports extends Command
{
    protected $signature = 'reports:run-scheduled';

    protected $description = 'Generate scheduled reports and store them for later download.';

    public function __construct(
        private readonly ReportFilterService $filterService,
        private readonly InventoryReportService $inventoryReport,
        private readonly HealthReportService $healthReport,
        private readonly MortalityReportService $mortalityReport,
        private readonly ExpenseReportService $expenseReport,
        private readonly SalesReportService $salesReport,
        private readonly MonthlyReportService $monthlyReport,
        private readonly QuarterlyReportService $quarterlyReport,
        private readonly ProfitabilityReportService $profitabilityReport,
        private readonly ReportPdfService $pdfService,
        private readonly ReportCsvService $csvService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $schedules = ReportSchedule::query()
            ->where('status', 'active')
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->get();

        if ($schedules->isEmpty()) {
            $this->info('No scheduled reports due.');

            return self::SUCCESS;
        }

        foreach ($schedules as $schedule) {
            $this->processSchedule($schedule);
        }

        return self::SUCCESS;
    }

    private function processSchedule(ReportSchedule $schedule): void
    {
        try {
            $filters = $this->filterService->normalize([
                'type' => $schedule->report_type,
                'cycle_id' => $schedule->cycle_id,
                ...($schedule->filters_json ?? []),
            ]);

            $reportData = $this->buildReportData($schedule->report_type, $filters);
            $generated = $this->buildStoredReport($schedule, $filters, $reportData);

            $schedule->update([
                'last_run_at' => now(),
                'next_run_at' => $this->nextRunAt($schedule),
                'last_error' => null,
            ]);

            $this->info('Generated scheduled report #'.$generated->id.' for '.$schedule->report_type.'.');
        } catch (Throwable $exception) {
            $schedule->update([
                'last_run_at' => now(),
                'last_error' => $exception->getMessage(),
                'next_run_at' => $this->nextRunAt($schedule),
            ]);

            Log::error('Scheduled report generation failed.', [
                'schedule_id' => $schedule->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function buildStoredReport(ReportSchedule $schedule, array $filters, array $reportData): GeneratedReport
    {
        if ($schedule->format === 'pdf') {
            $pdf = $this->pdfService->build(
                "reports.pdf.{$schedule->report_type}",
                [
                    'type' => $schedule->report_type,
                    'filters' => $filters,
                    'report' => $reportData,
                    'generatedAt' => now(),
                ],
                "{$schedule->report_type}-report",
                $reportData['charts'] ?? []
            );

            $generated = GeneratedReport::create([
                'report_type' => $schedule->report_type,
                'format' => 'pdf',
                'cycle_id' => $filters['cycle_id'] ?? null,
                'filters_json' => $filters,
                'generated_by' => $schedule->created_by,
                'schedule_id' => $schedule->id,
                'status' => 'generated',
                'file_path' => $pdf['stored_path'],
                'file_size' => strlen($pdf['content']),
                'generated_at' => now(),
            ]);

            return $generated;
        }

        [$headers, $rows] = $this->csvPayload($schedule->report_type, $reportData);
        $csv = $this->csvService->buildAndStore("{$schedule->report_type}-report", $headers, $rows);

        return GeneratedReport::create([
            'report_type' => $schedule->report_type,
            'format' => 'csv',
            'cycle_id' => $filters['cycle_id'] ?? null,
            'filters_json' => $filters,
            'generated_by' => $schedule->created_by,
            'schedule_id' => $schedule->id,
            'status' => 'generated',
            'file_path' => $csv['stored_path'],
            'file_size' => strlen($csv['content']),
            'generated_at' => now(),
        ]);
    }

    private function nextRunAt(ReportSchedule $schedule): ?\Illuminate\Support\Carbon
    {
        $runAt = $schedule->run_at ?? '08:00:00';
        $day = $schedule->day_of_month ?? 1;

        if ($schedule->frequency === 'monthly') {
            return now()->addMonthNoOverflow()->setDay($day)->setTimeFromTimeString($runAt);
        }

        if ($schedule->frequency === 'quarterly') {
            return now()->addQuarter()->setDay($day)->setTimeFromTimeString($runAt);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function buildReportData(string $type, array $filters): array
    {
        return match ($type) {
            'inventory' => $this->inventoryReport->generate($filters),
            'health' => $this->healthReport->generate($filters),
            'mortality' => $this->mortalityReport->generate($filters),
            'expense' => $this->expenseReport->generate($filters),
            'sales' => $this->salesReport->generate($filters),
            'monthly' => $this->monthlyReport->generate($filters),
            'quarterly' => $this->quarterlyReport->generate($filters),
            'profitability' => $this->profitabilityReport->generate($filters),
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $reportData
     * @return array{0: list<string>, 1: list<array<int|string, mixed>>}
     */
    private function csvPayload(string $type, array $reportData): array
    {
        $rows = $reportData['rows'] ?? [];

        return match ($type) {
            'inventory' => [
                ['Cycle', 'Stage', 'Status', 'Caretaker', 'Initial', 'Current', 'Active', 'Sold', 'Deceased'],
                collect($rows)->map(fn (array $row) => [
                    $row['cycle_code'] ?? '',
                    $row['stage'] ?? '',
                    $row['status'] ?? '',
                    $row['caretaker'] ?? '',
                    $row['initial_count'] ?? 0,
                    $row['current_count'] ?? 0,
                    $row['active_pigs'] ?? 0,
                    $row['sold_pigs'] ?? 0,
                    $row['deceased_pigs'] ?? 0,
                ])->all(),
            ],
            'health' => [
                ['Cycle', 'Due Today', 'Overdue', 'Completed Recently', 'Currently Affected', 'Total Incidents', 'Mortality'],
                collect($rows)->map(fn (array $row) => [
                    $row['cycle_code'] ?? '',
                    $row['due_today'] ?? 0,
                    $row['overdue'] ?? 0,
                    $row['completed_recently'] ?? 0,
                    $row['currently_affected'] ?? 0,
                    $row['total_incidents'] ?? 0,
                    $row['mortality'] ?? 0,
                ])->all(),
            ],
            'mortality' => [
                ['Date', 'Cycle', 'Affected', 'Suspected Cause', 'Reported By', 'Media Path'],
                collect($rows)->map(fn (array $row) => [
                    $row['date_reported'] ?? '',
                    $row['cycle_code'] ?? '',
                    $row['affected_count'] ?? 0,
                    $row['suspected_cause'] ?? '',
                    $row['reported_by'] ?? '',
                    $row['media_path'] ?? '',
                ])->all(),
            ],
            'expense' => [
                ['Date', 'Cycle', 'Category', 'Amount', 'Notes', 'Recorded By'],
                collect($rows)->map(fn (array $row) => [
                    $row['expense_date'] ?? '',
                    $row['cycle_code'] ?? '',
                    $row['category'] ?? '',
                    $row['amount'] ?? 0,
                    $row['notes'] ?? '',
                    $row['recorded_by'] ?? '',
                ])->all(),
            ],
            'sales' => [
                ['Date', 'Cycle', 'Buyer', 'Pigs Sold', 'Amount', 'Amount Paid', 'Payment Status'],
                collect($rows)->map(fn (array $row) => [
                    $row['sale_date'] ?? '',
                    $row['cycle_code'] ?? '',
                    $row['buyer'] ?? '',
                    $row['pigs_sold'] ?? 0,
                    $row['amount'] ?? 0,
                    $row['amount_paid'] ?? 0,
                    $row['payment_status'] ?? '',
                ])->all(),
            ],
            'profitability' => [
                ['Cycle', 'Status', 'Caretaker', 'Gross Income', 'Total Expenses', 'Net Profit/Loss', 'Finalized'],
                collect($rows)->map(fn (array $row) => [
                    $row['cycle_code'] ?? '',
                    $row['status'] ?? '',
                    $row['caretaker'] ?? '',
                    $row['gross_income'] ?? 0,
                    $row['total_expenses'] ?? 0,
                    $row['net_profit_or_loss'] ?? 0,
                    ($row['is_finalized'] ?? false) ? 'Yes' : 'No',
                ])->all(),
            ],
            'monthly', 'quarterly' => [
                ['Period', 'Total Sales', 'Total Collected', 'Total Expenses', 'Net Result'],
                ! empty($reportData['summary'] ?? null) ? [[
                    $reportData['summary']['period'] ?? '',
                    $reportData['summary']['total_sales'] ?? 0,
                    $reportData['summary']['total_collected'] ?? 0,
                    $reportData['summary']['total_expenses'] ?? 0,
                    $reportData['summary']['net_result'] ?? 0,
                ]] : [],
            ],
            default => [
                ['Metric', 'Value'],
                collect($reportData['summary'] ?? [])->map(fn ($value, $key) => [$key, $value])->values()->all(),
            ],
        };
    }
}
