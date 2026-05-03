<?php

namespace App\Services\Workflow;

use App\Events\Workflow\ReportFinalized;
use App\Models\LiquidationReport;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Generates liquidation reports for completed withdrawals.
 * Reconciles approved budget line-items with linked actual expenses (REQ-010, REQ-011).
 */
class ReportService
{
    public function __construct(
        private readonly WithdrawalLiquidationPdfService $pdfService
    ) {}

    /**
     * Generate a liquidation report for a withdrawal.
     * Pulls budget line-items from the resolution and actual expenses linked to the withdrawal.
     */
    public function generate(Withdrawal $withdrawal, User $user, ?string $summary = null): LiquidationReport
    {
        $resolution = $withdrawal->resolution()->with('lineItems')->first();
        $actualExpenses = $withdrawal->expenses()->get();

        // Build a detailed summary if not provided
        if (! $summary) {
            $approvedBudget = $resolution->grand_total ?? 0;
            $withdrawnAmount = (float) $withdrawal->amount;
            $actualTotal = (float) $actualExpenses->sum('amount');
            $variance = $approvedBudget - $actualTotal;

            $summary = "Liquidation Report for Resolution: {$resolution->title}\n"
                .'Approved Budget: ₱'.number_format($approvedBudget, 2)."\n"
                .'Withdrawn Amount: ₱'.number_format($withdrawnAmount, 2)."\n"
                .'Actual Expenses: ₱'.number_format($actualTotal, 2)."\n"
                .'Variance: ₱'.number_format($variance, 2)
                .($variance < 0 ? ' (OVER BUDGET)' : '')."\n"
                .'Remaining Balance: ₱'.number_format($resolution->remaining_balance, 2);

            // Append budget vs. actual line-items
            if ($resolution->lineItems->isNotEmpty()) {
                $summary .= "\n\n--- Budget Line Items ---";
                foreach ($resolution->lineItems as $li) {
                    $summary .= "\n• {$li->category}: {$li->description} "
                        ."({$li->quantity} {$li->unit} × ₱".number_format((float) $li->unit_cost, 2).')'
                        .' = ₱'.number_format((float) $li->total, 2);
                }
            }

            if ($actualExpenses->isNotEmpty()) {
                $summary .= "\n\n--- Actual Expenses ---";
                foreach ($actualExpenses as $exp) {
                    $summary .= "\n• {$exp->category}: "
                        .($exp->notes ?: 'No description')
                        .' = ₱'.number_format((float) $exp->amount, 2)
                        ." ({$exp->expense_date->format('M d, Y')})";
                }
            }
        }

        $report = LiquidationReport::updateOrCreate(
            ['withdrawal_id' => $withdrawal->id],
            [
                'generated_by' => $user->id,
                'summary' => $summary,
                'finalized_at' => now(),
            ]
        );

        try {
            $pdf = $this->pdfService->buildAndStore($report);

            $report->update([
                'report_file_path' => $pdf['stored_path'],
            ]);
        } catch (Throwable $exception) {
            Log::error('Liquidation report PDF generation failed.', [
                'report_id' => $report->id,
                'withdrawal_id' => $withdrawal->id,
                'message' => $exception->getMessage(),
            ]);
        }

        event(new ReportFinalized($report, $withdrawal));

        return $report->refresh();
    }

    /**
     * Get budget-vs-actual comparison data for a withdrawal.
     * Used by the Vue component to render the side-by-side table.
     *
     * @return array{budget: array, actual: array, totals: array}
     */
    public function getBudgetVsActual(Withdrawal $withdrawal): array
    {
        $resolution = $withdrawal->resolution()->with('lineItems')->first();
        $actualExpenses = $withdrawal->expenses()->get();

        $budgetByCategory = $resolution->lineItems
            ->groupBy('category')
            ->map(fn ($items) => [
                'category' => $items->first()->category,
                'budgeted' => (float) $items->sum('total'),
                'items' => $items->map(fn ($li) => [
                    'description' => $li->description,
                    'quantity' => (float) $li->quantity,
                    'unit' => $li->unit,
                    'unit_cost' => (float) $li->unit_cost,
                    'total' => (float) $li->total,
                ])->values()->toArray(),
            ])->values()->toArray();

        $actualByCategory = $actualExpenses
            ->groupBy('category')
            ->map(fn ($items) => [
                'category' => $items->first()->category,
                'actual' => (float) $items->sum('amount'),
                'items' => $items->map(fn ($exp) => [
                    'notes' => $exp->notes,
                    'amount' => (float) $exp->amount,
                    'date' => $exp->expense_date?->format('M d, Y'),
                ])->values()->toArray(),
            ])->values()->toArray();

        $budgetTotal = (float) $resolution->grand_total;
        $actualTotal = (float) $actualExpenses->sum('amount');

        return [
            'budget' => $budgetByCategory,
            'actual' => $actualByCategory,
            'totals' => [
                'budgeted' => $budgetTotal,
                'actual' => $actualTotal,
                'variance' => $budgetTotal - $actualTotal,
                'is_over_budget' => $actualTotal > $budgetTotal,
            ],
        ];
    }
}
