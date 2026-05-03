<?php

namespace App\Services\Workflow;

use App\Models\Resolution;

/**
 * Provides summary counts for the dashboard workflow cards (REQ-012).
 * Shows officers what needs attention across all resolutions.
 */
class WorkflowDashboardService
{
    /**
     * Get summary counts for dashboard cards.
     *
     * @return array{
     *   pending_approval: int,
     *   ready_for_dswd: int,
     *   awaiting_dswd: int,
     *   ready_for_withdrawal: int,
     *   pending_reports: int,
     *   finalized: int,
     *   over_budget_warnings: int,
     *   total_active: int,
     * }
     */
    public function getSummary(): array
    {
        // Count resolutions by status
        $counts = Resolution::query()
            ->selectRaw("status, COUNT(*) as total")
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // Resolutions pending approval (status = pending_approval)
        $pendingApproval = $counts['pending_approval'] ?? 0;

        // Approved but not yet submitted to DSWD
        $readyForDswd = $counts['approved'] ?? 0;

        // Submitted to DSWD, waiting for response
        $awaitingDswd = Resolution::where('status', 'dswd_submitted')
            ->whereHas('dswdSubmission', fn ($q) => $q->where('status', 'submitted'))
            ->count();

        // DSWD approved, ready for withdrawal
        $readyForWithdrawal = Resolution::where('status', 'dswd_submitted')
            ->whereHas('dswdSubmission', fn ($q) => $q->where('status', 'approved'))
            ->count();

        // Withdrawn but no liquidation report yet
        $pendingReports = Resolution::where('status', 'withdrawn')
            ->whereHas('withdrawals', fn ($q) => $q
                ->where('status', 'pending')
                ->whereDoesntHave('liquidationReport'))
            ->count();

        // Finalized resolutions
        $finalized = $counts['finalized'] ?? 0;

        // Over-budget warnings (withdrawn resolutions where actual expenses > budget)
        $overBudget = Resolution::where('status', 'withdrawn')
            ->get()
            ->filter(fn ($r) => $r->remaining_balance < 0)
            ->count();

        return [
            'pending_approval' => $pendingApproval,
            'ready_for_dswd' => $readyForDswd,
            'awaiting_dswd' => $awaitingDswd,
            'ready_for_withdrawal' => $readyForWithdrawal,
            'pending_reports' => $pendingReports,
            'finalized' => $finalized,
            'over_budget_warnings' => $overBudget,
            'total_active' => Resolution::whereNotIn('status', ['finalized'])->count(),
        ];
    }
}
