<?php

namespace App\Services\PigRegistry;

use App\Models\PigCycle;
use App\Models\ProfitabilitySnapshot;

class ProfitabilityValidationService
{
    /**
     * Validate that a cycle is ready for finalization.
     * Returns an array of warnings (non-blocking) and a boolean for canFinalize.
     *
     * @return array{can_finalize: bool, blocking_errors: list<string>, warnings: list<string>, checklist: list<array{label: string, passed: bool, message: string}>}
     */
    public function validate(PigCycle $cycle, array $computed, ?ProfitabilitySnapshot $latestSnapshot = null): array
    {
        $blockingErrors = [];
        $warnings = [];
        $checklist = [];

        $checklist[] = $this->checkSalesRecorded($cycle, $computed, $blockingErrors);
        $checklist[] = $this->checkExpensesRecorded($cycle, $computed, $blockingErrors);
        $checklist[] = $this->checkCycleArchived($cycle, $blockingErrors);
        $checklist[] = $this->checkPendingPayments($computed, $warnings);
        $checklist[] = $this->checkNetLoss($computed, $warnings);
        $checklist[] = $this->checkMissingReceipts($cycle, $warnings);
        $checklist[] = $this->checkHighFeedCostRatio($computed, $warnings);
        $checklist[] = $this->checkDataChangedAfterFinalization($latestSnapshot, $computed, $warnings);

        return [
            'can_finalize' => count($blockingErrors) === 0,
            'blocking_errors' => $blockingErrors,
            'warnings' => $warnings,
            'checklist' => $checklist,
        ];
    }

    /**
     * @param  list<string>  $blockingErrors
     * @return array{label: string, passed: bool, message: string}
     */
    private function checkSalesRecorded(PigCycle $cycle, array $computed, array &$blockingErrors): array
    {
        if (! $computed['has_sales']) {
            $blockingErrors[] = 'No sales have been recorded for this cycle.';

            return [
                'label' => 'Sales recorded',
                'passed' => false,
                'message' => 'No sales found. Record at least one sale before finalizing.',
            ];
        }

        return [
            'label' => 'Sales recorded',
            'passed' => true,
            'message' => 'Sales have been recorded.',
        ];
    }

    /**
     * @param  list<string>  $blockingErrors
     * @return array{label: string, passed: bool, message: string}
     */
    private function checkExpensesRecorded(PigCycle $cycle, array $computed, array &$blockingErrors): array
    {
        if (! $computed['has_expenses']) {
            $blockingErrors[] = 'No expenses have been recorded for this cycle.';

            return [
                'label' => 'Expenses recorded',
                'passed' => false,
                'message' => 'No expenses found. Record at least one expense before finalizing.',
            ];
        }

        return [
            'label' => 'Expenses recorded',
            'passed' => true,
            'message' => 'Expenses have been recorded.',
        ];
    }

    /**
     * @param  list<string>  $blockingErrors
     * @return array{label: string, passed: bool, message: string}
     */
    private function checkCycleArchived(PigCycle $cycle, array &$blockingErrors): array
    {
        if (! $cycle->isArchived()) {
            $blockingErrors[] = 'The cycle must be completed, sold, or closed before finalizing profitability.';

            return [
                'label' => 'Cycle completed or closed',
                'passed' => false,
                'message' => 'This cycle is still active. Close or mark it as sold before finalizing.',
            ];
        }

        return [
            'label' => 'Cycle completed or closed',
            'passed' => true,
            'message' => 'Cycle is in archived status.',
        ];
    }

    /**
     * @param  list<string>  $warnings
     * @return array{label: string, passed: bool, message: string}
     */
    private function checkPendingPayments(array $computed, array &$warnings): array
    {
        $hasPending = $computed['has_pending_payments'] ?? false;
        $receivables = (float) ($computed['receivables'] ?? 0);

        if ($hasPending || $receivables > 0) {
            $warnings[] = "Pending collection of ₱".number_format($receivables, 2).'. Verify that receivables are accounted for before finalizing shares.';

            return [
                'label' => 'Pending collection / receivables',
                'passed' => false,
                'message' => "Receivables of ₱".number_format($receivables, 2).' exist. Profitability uses total recorded sales regardless of collection status.',
            ];
        }

        return [
            'label' => 'Pending collection / receivables',
            'passed' => true,
            'message' => 'All sales are fully collected.',
        ];
    }

    /**
     * @param  list<string>  $warnings
     * @return array{label: string, passed: bool, message: string}
     */
    private function checkNetLoss(array $computed, array &$warnings): array
    {
        $netProfitOrLoss = (float) ($computed['net_profit_or_loss'] ?? 0);

        if ($netProfitOrLoss < 0) {
            $warnings[] = 'This cycle has a net loss. All stakeholder shares will be ₱0.00.';

            return [
                'label' => 'Net profit or loss reviewed',
                'passed' => true,
                'message' => "This cycle has a loss of ₱".number_format(abs($netProfitOrLoss), 2).'. No distributable profit.',
            ];
        }

        return [
            'label' => 'Net profit or loss reviewed',
            'passed' => true,
            'message' => $netProfitOrLoss > 0
                ? "Net profit of ₱".number_format($netProfitOrLoss, 2).' confirmed.'
                : 'Break-even result confirmed.',
        ];
    }

    /**
     * @param  list<string>  $warnings
     * @return array{label: string, passed: bool, message: string}
     */
    private function checkMissingReceipts(PigCycle $cycle, array &$warnings): array
    {
        $expensesWithoutReceipt = $cycle->expenses()
            ->whereNull('receipt_path')
            ->orWhere('receipt_path', '')
            ->count();

        if ($expensesWithoutReceipt > 0) {
            $warnings[] = "{$expensesWithoutReceipt} expense(s) have no uploaded receipt. Review for completeness.";

            return [
                'label' => 'Expense receipts uploaded',
                'passed' => false,
                'message' => "{$expensesWithoutReceipt} expense(s) missing receipts. Receipts are recommended for audit.",
            ];
        }

        return [
            'label' => 'Expense receipts uploaded',
            'passed' => true,
            'message' => 'All expenses have uploaded receipts.',
        ];
    }

    /**
     * @param  list<string>  $warnings
     * @return array{label: string, passed: bool, message: string}
     */
    private function checkHighFeedCostRatio(array $computed, array &$warnings): array
    {
        $totalExpenses = (float) ($computed['total_expenses'] ?? 0);
        $feedCost = (float) ($computed['expense_breakdown']['feed'] ?? 0);

        if ($totalExpenses <= 0) {
            return [
                'label' => 'Feed cost ratio',
                'passed' => true,
                'message' => 'No expenses to evaluate.',
            ];
        }

        $feedRatio = $feedCost / $totalExpenses;

        if ($feedRatio > 0.60) {
            $warnings[] = 'Feed costs are '.round($feedRatio * 100)."% of total expenses. Review for cost control.";

            return [
                'label' => 'Feed cost ratio',
                'passed' => false,
                'message' => 'Feed is '.round($feedRatio * 100)."% of expenses. This may be worth reviewing.",
            ];
        }

        return [
            'label' => 'Feed cost ratio',
            'passed' => true,
            'message' => 'Feed cost is within normal range.',
        ];
    }

    /**
     * @param  list<string>  $warnings
     * @return array{label: string, passed: bool, message: string}
     */
    private function checkDataChangedAfterFinalization(?ProfitabilitySnapshot $latestSnapshot, array $computed, array &$warnings): array
    {
        if ($latestSnapshot === null) {
            return [
                'label' => 'Data not changed since last snapshot',
                'passed' => true,
                'message' => 'No prior snapshot exists. This will be the first finalized version.',
            ];
        }

        return [
            'label' => 'Data not changed since last snapshot',
            'passed' => true,
            'message' => 'Prior snapshot version '.$latestSnapshot->version_number.' exists.',
        ];
    }
}