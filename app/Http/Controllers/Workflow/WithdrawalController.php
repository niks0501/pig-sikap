<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workflow\StoreWithdrawalRequest;
use App\Models\LiquidationReport;
use App\Models\Resolution;
use App\Models\Withdrawal;
use App\Services\Workflow\EligibilityService;
use App\Services\Workflow\ReportService;
use App\Services\Workflow\WithdrawalService;
use App\Services\Workflow\WorkflowTransitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Manages fund withdrawal requests and liquidation reports.
 */
class WithdrawalController extends Controller
{
    use RecordsAuditTrail;

    public function __construct(
        private readonly WithdrawalService $withdrawalService,
        private readonly EligibilityService $eligibilityService,
        private readonly ReportService $reportService,
        private readonly WorkflowTransitionService $transitionService
    ) {}

    /**
     * Show the withdrawal form for a resolution.
     */
    public function create(Resolution $resolution): View|RedirectResponse
    {
        $eligibility = $this->eligibilityService->canWithdraw($resolution);

        if (! $eligibility['eligible']) {
            return redirect()
                ->route('workflow.resolutions.show', $resolution)
                ->withErrors(['resolution' => $eligibility['reasons']]);
        }

        $resolution->load(['meeting:id,title,date', 'lineItems', 'withdrawals']);

        return view('workflow.withdrawals-create', [
            'resolution' => $resolution,
            'eligibility' => $eligibility,
        ]);
    }

    /**
     * Store a withdrawal request.
     */
    public function store(StoreWithdrawalRequest $request, Resolution $resolution): RedirectResponse|JsonResponse
    {
        try {
            $withdrawal = $this->withdrawalService->createFromResolution(
                $resolution,
                $request->validated(),
                $request->user()
            );
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $e->errors()], 422);
            }

            return back()->withErrors($e->errors())->withInput();
        }

        $this->recordAudit(
            $request,
            'withdrawal_created',
            'Created withdrawal of ₱'.number_format((float) $withdrawal->amount, 2)." for resolution #{$resolution->id}",
            'workflow',
            [
                'withdrawal_id' => $withdrawal->id,
                'resolution_id' => $resolution->id,
                'amount' => (float) $withdrawal->amount,
            ]
        );

        if ($request->expectsJson()) {
            $resolution->refresh();

            return response()->json([
                'message' => 'Withdrawal request created successfully.',
                'withdrawal' => [
                    'id' => $withdrawal->id,
                    'amount' => (float) $withdrawal->amount,
                    'status' => $withdrawal->status,
                    'requested_at' => $withdrawal->requested_at?->format('M d, Y'),
                    'requester' => $withdrawal->requester ? ['name' => $withdrawal->requester->name] : null,
                    'notes' => $withdrawal->notes,
                    'generate_report_url' => route('workflow.withdrawals.report', $withdrawal),
                    'proof_file_url' => $withdrawal->proofFileUrl(),
                    'bank_reference' => $withdrawal->bank_reference,
                ],
                'resolution' => [
                    'grand_total' => (float) $resolution->grand_total,
                    'total_withdrawn' => (float) $resolution->total_withdrawn,
                    'remaining_balance' => (float) $resolution->remaining_balance,
                    'status' => $resolution->status,
                    'workflow_status' => $resolution->workflow_status,
                ],
                'redirect_url' => route('workflow.resolutions.show', $resolution),
            ], 201);
        }

        return redirect()
            ->route('workflow.resolutions.show', $resolution)
            ->with('status', 'Withdrawal request created successfully.');
    }

    /**
     * Generate a liquidation report for a withdrawal.
     * Marks the resolution as finalized and archived.
     */
    public function generateReport(Request $request, Withdrawal $withdrawal): RedirectResponse|JsonResponse
    {
        $this->ensureWithdrawalCanBeLiquidated($withdrawal);

        // Mark the withdrawal/resolution complete before PDF generation so the official
        // document reflects the same final status users see in the workflow screen.
        $resolution = $withdrawal->resolution;

        if ($resolution->status !== 'finalized') {
            $resolution->update(['status' => 'finalized']);
        }

        if ($withdrawal->status !== 'completed') {
            $withdrawal->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }

        // Archive the resolution after liquidation report is generated
        if (in_array($resolution->workflow_status, ['withdrawn'])) {
            $this->transitionService->transitionToArchived($resolution);
        }

        $report = $this->reportService->generate(
            $withdrawal->fresh(),
            $request->user(),
            $request->input('summary')
        );

        $this->recordAudit(
            $request,
            'report_generated',
            "Generated liquidation report for withdrawal #{$withdrawal->id}",
            'workflow',
            [
                'report_id' => $report->id,
                'withdrawal_id' => $withdrawal->id,
            ]
        );

        if ($request->expectsJson()) {
            $withdrawal->load('liquidationReport');

            return response()->json([
                'message' => 'Liquidation report generated successfully.',
                'report' => $report,
                'preview_url' => route('workflow.withdrawals.report.preview', [$withdrawal, $report]),
                'download_url' => route('workflow.withdrawals.report.download', [$withdrawal, $report]),
                'withdrawal' => [
                    'id' => $withdrawal->id,
                    'status' => $withdrawal->status,
                    'completed_at' => $withdrawal->completed_at?->format('M d, Y'),
                    'has_report' => true,
                    'preview_url' => route('workflow.withdrawals.report.preview', [$withdrawal, $report]),
                    'download_url' => route('workflow.withdrawals.report.download', [$withdrawal, $report]),
                ],
                'resolution' => [
                    'status' => $withdrawal->resolution->fresh()->status,
                    'remaining_balance' => (float) $withdrawal->resolution->remaining_balance,
                    'workflow_status' => $withdrawal->resolution->workflow_status,
                ],
            ]);
        }

        return redirect()
            ->route('workflow.resolutions.show', $withdrawal->resolution)
            ->with('status', 'Liquidation report generated and resolution finalized.');
    }

    /**
     * Update the liquidation status of a report (draft → submitted → reviewed → approved → returned).
     */
    public function updateLiquidationStatus(Request $request, Withdrawal $withdrawal, LiquidationReport $report): RedirectResponse|JsonResponse
    {
        $this->ensureReportBelongsToWithdrawal($withdrawal, $report);

        $validated = $request->validate([
            'liquidation_status' => ['required', 'string', 'in:'.implode(',', LiquidationReport::STATUSES)],
        ]);

        $report->update(['liquidation_status' => $validated['liquidation_status']]);

        $this->recordAudit(
            $request,
            'liquidation_status_updated',
            "Updated liquidation status to '{$report->liquidation_status}' for report #{$report->id}",
            'workflow',
            [
                'report_id' => $report->id,
                'withdrawal_id' => $withdrawal->id,
                'liquidation_status' => $report->liquidation_status,
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Liquidation status updated.',
                'report' => [
                    'id' => $report->id,
                    'liquidation_status' => $report->liquidation_status,
                    'liquidation_status_label' => $report->liquidation_status_label,
                    'liquidation_status_color' => $report->liquidation_status_color,
                ],
            ]);
        }

        return redirect()
            ->route('workflow.resolutions.show', $withdrawal->resolution)
            ->with('status', 'Liquidation status updated.');
    }

    /**
     * Preview the stored official liquidation PDF in the browser.
     */
    public function previewPdf(Withdrawal $withdrawal, LiquidationReport $report): Response
    {
        $this->ensureReportBelongsToWithdrawal($withdrawal, $report);

        return $this->pdfResponse($report, 'inline');
    }

    /**
     * Download the stored official liquidation PDF.
     */
    public function downloadPdf(Withdrawal $withdrawal, LiquidationReport $report): Response
    {
        $this->ensureReportBelongsToWithdrawal($withdrawal, $report);

        return $this->pdfResponse($report, 'attachment');
    }

    /**
     * Get budget-vs-actual expense comparison for a withdrawal (REQ-010).
     */
    public function budgetVsActual(Withdrawal $withdrawal): JsonResponse
    {
        $data = $this->reportService->getBudgetVsActual($withdrawal);

        return response()->json($data);
    }

    private function ensureWithdrawalCanBeLiquidated(Withdrawal $withdrawal): void
    {
        $withdrawal->loadMissing([
            'resolution.dswdSubmission',
            'resolution.approvals',
            'resolution.lineItems',
            'resolution.withdrawals',
        ]);

        $resolution = $withdrawal->resolution;
        $reasons = [];

        if ($withdrawal->status === 'cancelled') {
            $reasons[] = 'Cancelled withdrawals cannot be liquidated.';
        }

        if (! $resolution->hasMetApprovalThreshold()) {
            $reasons[] = 'Member approval must reach 75% before generating a liquidation report.';
        }

        if (! $resolution->dswdSubmission || $resolution->dswdSubmission->status !== 'approved') {
            $reasons[] = 'DSWD approval is required before generating a liquidation report.';
        }

        if ($reasons !== []) {
            throw ValidationException::withMessages(['withdrawal' => $reasons]);
        }
    }

    private function ensureReportBelongsToWithdrawal(Withdrawal $withdrawal, LiquidationReport $report): void
    {
        abort_unless($report->withdrawal_id === $withdrawal->id, 404);
    }

    private function pdfResponse(LiquidationReport $report, string $disposition): Response
    {
        $path = $report->report_file_path;

        abort_if(! $path || ! Storage::disk('public')->exists($path), 404, 'Liquidation report PDF file was not found.');

        $fileName = basename($path);

        return response(Storage::disk('public')->get($path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$fileName.'"',
        ]);
    }
}
