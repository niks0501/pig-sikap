<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workflow\StoreWithdrawalRequest;
use App\Models\Resolution;
use App\Models\Withdrawal;
use App\Services\Workflow\EligibilityService;
use App\Services\Workflow\ReportService;
use App\Services\Workflow\WithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        private readonly ReportService $reportService
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
            "Created withdrawal of ₱" . number_format((float) $withdrawal->amount, 2) . " for resolution #{$resolution->id}",
            'workflow',
            [
                'withdrawal_id' => $withdrawal->id,
                'resolution_id' => $resolution->id,
                'amount' => (float) $withdrawal->amount,
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Withdrawal request created successfully.',
                'withdrawal' => $withdrawal,
                'redirect_url' => route('workflow.resolutions.show', $resolution),
            ], 201);
        }

        return redirect()
            ->route('workflow.resolutions.show', $resolution)
            ->with('status', 'Withdrawal request created successfully.');
    }

    /**
     * Generate a liquidation report for a withdrawal.
     */
    public function generateReport(Request $request, Withdrawal $withdrawal): RedirectResponse|JsonResponse
    {
        $report = $this->reportService->generate(
            $withdrawal,
            $request->user(),
            $request->input('summary')
        );

        // Finalize the resolution
        $resolution = $withdrawal->resolution;

        if ($resolution->status !== 'finalized') {
            $resolution->update(['status' => 'finalized']);
        }

        $withdrawal->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

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
            return response()->json([
                'message' => 'Liquidation report generated successfully.',
                'report' => $report,
            ]);
        }

        return redirect()
            ->route('workflow.resolutions.show', $withdrawal->resolution)
            ->with('status', 'Liquidation report generated and resolution finalized.');
    }

    /**
     * Get budget-vs-actual expense comparison for a withdrawal (REQ-010).
     */
    public function budgetVsActual(Withdrawal $withdrawal): JsonResponse
    {
        $data = $this->reportService->getBudgetVsActual($withdrawal);

        return response()->json($data);
    }
}
