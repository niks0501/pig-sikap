<?php

namespace App\Services\PigRegistry;

use App\Models\Pig;
use App\Models\PigCycle;
use App\Models\PigCycleExpense;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyzePigCycleService
{
    public function __construct(
        private readonly CycleHealthSummaryService $cycleHealthSummaryService,
        private readonly CycleSummaryService $cycleSummaryService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(PigCycle $cycle): array
    {
        $daysElapsed = $cycle->days_since_acquisition;
        $daysRemaining = $cycle->days_until_ready_for_sale;

        $countSummary = $this->buildCountSummary($cycle);
        $expenseSummary = $this->buildExpenseSummary($cycle);
        $profitabilitySummary = $this->buildProfitabilitySummary(
            $expenseSummary['total_cycle_expense'],
            $expenseSummary['total_cycle_sales']
        );
        $healthSummary = $this->cycleHealthSummaryService->handle($cycle);

        return [
            'countdown' => [
                'expected_ready_for_sale_date' => $cycle->expected_ready_for_sale_date,
                'expected_harvest_month' => $cycle->expected_harvest_month,
                'days_since_acquisition' => $daysElapsed,
                'days_until_ready_for_sale' => $daysRemaining,
                'is_near_harvest_window' => $cycle->is_near_harvest_window,
                'is_overdue_for_sale_review' => $cycle->is_overdue_for_sale_review,
            ],
            'counts' => $countSummary,
            'expenses' => $expenseSummary,
            'profitability' => $profitabilitySummary,
            'health' => $healthSummary,
            'suggestions' => $this->buildSuggestions($cycle, $daysElapsed, $daysRemaining, $countSummary),
            'warnings' => $this->buildWarnings(
                $cycle,
                $daysElapsed,
                $daysRemaining,
                $countSummary,
                $expenseSummary,
                $profitabilitySummary
            ),
            'report_snapshot' => [
                'cycle_profile' => [
                    'batch_code' => $cycle->batch_code,
                    'stage' => $cycle->stage,
                    'status' => $cycle->status,
                    'date_of_purchase' => $cycle->date_of_purchase instanceof Carbon
                        ? $cycle->date_of_purchase->toDateString()
                        : ($cycle->date_of_purchase !== null ? (string) $cycle->date_of_purchase : null),
                ],
                'pig_count_summary' => $countSummary,
                'mortality_summary' => [
                    'deceased_count' => $countSummary['deceased_count'],
                    'mortality_rate' => $countSummary['mortality_rate'],
                ],
                'expense_summary' => $expenseSummary,
                'sales_summary' => [
                    'total_sales' => $expenseSummary['total_cycle_sales'],
                    'sold_count' => $countSummary['sold_count'],
                ],
                'profitability_summary' => $profitabilitySummary,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCountSummary(PigCycle $cycle): array
    {
        return $this->cycleSummaryService->forCycle($cycle);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildExpenseSummary(PigCycle $cycle): array
    {
        $grouped = $cycle->expenses()
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->pluck('total', 'category');

        $breakdown = [];

        foreach (PigCycleExpense::CATEGORIES as $category) {
            $breakdown[$category] = round((float) ($grouped[$category] ?? 0), 2);
        }

        $totalExpenses = round(array_sum($breakdown), 2);
        $totalSales = round((float) $cycle->sales()->sum('amount'), 2);

        return [
            'breakdown' => $breakdown,
            'total_cycle_expense' => $totalExpenses,
            'total_cycle_sales' => $totalSales,
        ];
    }

    /**
     * @return array<string, float>
     */
    private function buildProfitabilitySummary(float $totalExpenses, float $totalSales): array
    {
        $grossIncome = $totalSales;
        $netProfitOrLoss = round($grossIncome - $totalExpenses, 2);
        $distributableProfit = max($netProfitOrLoss, 0.0);

        return [
            'gross_income' => $grossIncome,
            'net_profit_or_loss' => $netProfitOrLoss,
            'caretaker_share' => round($distributableProfit * 0.50, 2),
            'member_share' => round($distributableProfit * 0.25, 2),
            'association_share' => round($distributableProfit * 0.25, 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $countSummary
     * @return list<array<string, mixed>>
     */
    private function buildSuggestions(
        PigCycle $cycle,
        ?int $daysElapsed,
        ?int $daysRemaining,
        array $countSummary
    ): array {
        $suggestions = [];
        $isArchived = $cycle->isArchived();

        $recommendedStage = $this->recommendedStage($daysElapsed);

        if (! $isArchived && $recommendedStage !== null && $recommendedStage !== $cycle->stage) {
            $suggestions[] = [
                'code' => 'stage_progression',
                'title' => 'Suggested Stage Progression',
                'message' => "This cycle has reached the {$recommendedStage} period based on acquisition timing.",
                'suggested_stage' => $recommendedStage,
                'suggested_status' => $cycle->status,
            ];
        }

        if (
            ! $isArchived
            && is_int($daysRemaining)
            && $daysRemaining <= 0
            && ! in_array($cycle->status, ['Ready for Sale', 'Sold', 'Closed'], true)
        ) {
            $suggestions[] = [
                'code' => 'ready_for_sale',
                'title' => 'Ready-for-Sale Suggestion',
                'message' => 'This cycle has reached the expected sale window. Consider updating status to Ready for Sale.',
                'suggested_stage' => $cycle->stage === 'Completed' ? $cycle->stage : 'For Sale',
                'suggested_status' => 'Ready for Sale',
            ];
        }

        if (! $isArchived && $cycle->is_near_harvest_window) {
            $suggestions[] = [
                'code' => 'near_harvest',
                'title' => 'Near Harvest Window',
                'message' => 'This cycle is within 14 days of the expected ready-for-sale date. Prepare review and market checks.',
                'suggested_stage' => $cycle->stage,
                'suggested_status' => 'Under Monitoring',
            ];
        }

        if (! $isArchived && (int) $countSummary['remaining_count'] <= 0) {
            $suggestions[] = [
                'code' => 'completion',
                'title' => 'Completion Suggestion',
                'message' => 'No active pigs remain in this cycle. Consider closing this cycle after final verification.',
                'suggested_stage' => 'Completed',
                'suggested_status' => 'Closed',
            ];
        }

        return $suggestions;
    }

    /**
     * @param  array<string, mixed>  $countSummary
     * @param  array<string, mixed>  $expenseSummary
     * @param  array<string, float>  $profitabilitySummary
     * @return list<array<string, mixed>>
     */
    private function buildWarnings(
        PigCycle $cycle,
        ?int $daysElapsed,
        ?int $daysRemaining,
        array $countSummary,
        array $expenseSummary,
        array $profitabilitySummary
    ): array {
        $warnings = [];

        if ($cycle->is_overdue_for_sale_review && ! $cycle->isArchived()) {
            $warnings[] = [
                'code' => 'overdue_sale_window',
                'severity' => 'warning',
                'message' => 'Cycle is past expected ready-for-sale date and requires review.',
            ];
        }

        if ((float) $countSummary['mortality_rate'] >= 15.0) {
            $warnings[] = [
                'code' => 'high_mortality',
                'severity' => 'warning',
                'message' => 'Unusually high mortality detected. Please review health and handling records.',
            ];
        }

        if ($cycle->last_reviewed_at instanceof Carbon && $cycle->last_reviewed_at->diffInDays(now()) >= 14) {
            $warnings[] = [
                'code' => 'stale_cycle_updates',
                'severity' => 'info',
                'message' => 'No significant cycle review has been recorded for 14 days.',
            ];
        }

        if (
            ! $cycle->isArchived()
            && is_int($daysRemaining)
            && $daysRemaining <= 0
            && (float) $expenseSummary['total_cycle_sales'] <= 0
            && (int) $countSummary['sold_count'] === 0
        ) {
            $warnings[] = [
                'code' => 'ready_but_no_sales',
                'severity' => 'warning',
                'message' => 'Cycle reached expected sale period but no sales are recorded yet.',
            ];
        }

        if ((float) $expenseSummary['total_cycle_sales'] > 0 && (float) $expenseSummary['total_cycle_expense'] > (float) $expenseSummary['total_cycle_sales']) {
            $warnings[] = [
                'code' => 'expense_pressure',
                'severity' => 'warning',
                'message' => 'Cycle expenses are higher than cycle sales so far.',
            ];
        }

        if ((float) $profitabilitySummary['net_profit_or_loss'] < 0 && is_int($daysElapsed) && $daysElapsed >= 120) {
            $warnings[] = [
                'code' => 'net_loss_after_target_window',
                'severity' => 'critical',
                'message' => 'Cycle is already in expected sale period but currently operating at a net loss.',
            ];
        }

        return $warnings;
    }

    private function recommendedStage(?int $daysElapsed): ?string
    {
        if (! is_int($daysElapsed)) {
            return null;
        }

        if ($daysElapsed < 30) {
            return 'Weaning';
        }

        if ($daysElapsed < 75) {
            return 'Growing';
        }

        if ($daysElapsed < 120) {
            return 'Fattening';
        }

        return 'For Sale';
    }
}
