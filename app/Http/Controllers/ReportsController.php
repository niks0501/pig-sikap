<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Requests\Reports\GenerateReportRequest;
use App\Models\GeneratedReport;
use App\Models\PigCycle;
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
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReportsController extends Controller
{
    use RecordsAuditTrail;

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
    ) {}

    public function index(Request $request): View
    {
        return view('reports.index');
    }

    public function generate(Request $request, string $type): View
    {
        $this->authorizeType($type);

        $cycles = PigCycle::query()
            ->activeRecords()
            ->orderByDesc('created_at')
            ->get(['id', 'batch_code', 'stage', 'status']);

        return view('reports.generate', [
            'type' => $type,
            'cycles' => $cycles,
            'actionUrl' => route('reports.preview', ['type' => $type]),
        ]);
    }

    public function preview(GenerateReportRequest $request, string $type): View
    {
        $this->authorizeType($type);

        try {
            $filters = $this->filterService->normalize($request->validated());
            $reportData = $this->buildReportData($type, $filters);

            $this->recordAudit(
                $request,
                'generate_preview',
                "Generated {$type} report preview",
                'reports',
                [
                    'type' => $type,
                    'filters' => $filters,
                ]
            );

            session()->flash('status', ucfirst($type).' report generated successfully.');

            return view('reports.preview', [
                'type' => $type,
                'filters' => $filters,
                'report' => $reportData,
                'previewUrl' => route('reports.preview', ['type' => $type]),
                'pdfUrl' => route('reports.pdf', ['type' => $type]).'?'.http_build_query($filters),
                'csvUrl' => route('reports.csv', ['type' => $type]).'?'.http_build_query($filters),
            ]);
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'Failed to generate '.ucfirst($type).' report. Please try again.');
            return redirect()->route('reports.generate', ['type' => $type]);
        }
    }

    public function downloadPdf(GenerateReportRequest $request, string $type): Response
    {
        $this->authorizeType($type);

        try {
            $filters = $this->filterService->normalize($request->validated());
            $reportData = $this->buildReportData($type, $filters);

            $pdf = $this->pdfService->build(
                "reports.pdf.{$type}",
                [
                    'type' => $type,
                    'filters' => $filters,
                    'report' => $reportData,
                    'generatedAt' => now(),
                ],
                "{$type}-report"
            );

            $generated = GeneratedReport::create([
                'report_type' => $type,
                'format' => 'pdf',
                'cycle_id' => $filters['cycle_id'] ?? null,
                'filters_json' => $filters,
                'generated_by' => $request->user()?->id,
                'status' => 'generated',
                'file_path' => $pdf['stored_path'],
                'file_size' => strlen($pdf['content']),
                'generated_at' => now(),
            ]);

            $this->recordAudit(
                $request,
                'generate_pdf',
                "Generated {$type} report PDF",
                'reports',
                [
                    'type' => $type,
                    'generated_report_id' => $generated->id,
                    'filters' => $filters,
                ]
            );

            session()->flash('status', ucfirst($type).' PDF downloaded successfully.');

            return response($pdf['content'], 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$pdf['file_name'].'"',
            ]);
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'Failed to generate '.ucfirst($type).' PDF. Please try again.');
            return redirect()->route('reports.generate', ['type' => $type]);
        }
    }

    public function downloadCsv(GenerateReportRequest $request, string $type): Response
    {
        $this->authorizeType($type);

        try {
            $filters = $this->filterService->normalize($request->validated());
            $reportData = $this->buildReportData($type, $filters);

            [$headers, $rows] = $this->csvPayload($type, $reportData);

            $csv = $this->csvService->buildAndStore("{$type}-report", $headers, $rows);

            $generated = GeneratedReport::create([
                'report_type' => $type,
                'format' => 'csv',
                'cycle_id' => $filters['cycle_id'] ?? null,
                'filters_json' => $filters,
                'generated_by' => $request->user()?->id,
                'status' => 'generated',
                'file_path' => $csv['stored_path'],
                'file_size' => strlen($csv['content']),
                'generated_at' => now(),
            ]);

            $this->recordAudit(
                $request,
                'export_csv',
                "Exported {$type} report CSV",
                'reports',
                [
                    'type' => $type,
                    'generated_report_id' => $generated->id,
                    'filters' => $filters,
                ]
            );

            session()->flash('status', ucfirst($type).' CSV exported successfully.');

            return response($csv['content'], 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.$csv['file_name'].'"',
            ]);
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'Failed to export '.ucfirst($type).' CSV. Please try again.');
            return redirect()->route('reports.generate', ['type' => $type]);
        }
    }

    private function authorizeType(string $type): void
    {
        Gate::authorize('generate-report', $type);
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
            default => [
                ['Metric', 'Value'],
                collect($reportData['summary'] ?? [])->map(fn ($value, $key) => [$key, $value])->values()->all(),
            ],
        };
    }
}
