<?php

namespace App\Services\Dashboard;

use App\Models\AssociationExpense;
use App\Models\AuditTrail;
use App\Models\CycleHealthIncident;
use App\Models\CycleHealthTask;
use App\Models\Pig;
use App\Models\PigCycle;
use App\Models\PigCycleExpense;
use App\Models\PigCycleSale;
use App\Models\ProfitabilitySnapshot;
use App\Models\Resolution;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OverallDashboardService
{
    /**
     * Get the full aggregated dashboard payload.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function getOverview(array $filters = []): array
    {
        $cycleId = $filters['cycle_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $pigStatus = $filters['pig_status'] ?? null;
        $pigSex = $filters['pig_sex'] ?? null;

        // Base queries
        $pigQuery = Pig::query();
        $cycleExpenseQuery = PigCycleExpense::query();
        $assocExpenseQuery = AssociationExpense::query();
        $saleQuery = PigCycleSale::query();
        $healthIncidentQuery = CycleHealthIncident::query();
        $healthTaskQuery = CycleHealthTask::query();
        $resolutionQuery = Resolution::query();
        $withdrawalQuery = Withdrawal::query();
        $auditQuery = AuditTrail::query()->with('user:id,name,email,role_id');
        $cycleQuery = PigCycle::query()->activeRecords();

        // Apply cycle filter to relevant queries
        if ($cycleId) {
            $pigQuery->where('batch_id', $cycleId);
            $cycleExpenseQuery->where('batch_id', $cycleId);
            $saleQuery->where('batch_id', $cycleId);
            $healthIncidentQuery->where('batch_id', $cycleId);
            $healthTaskQuery->where('batch_id', $cycleId);
            $cycleQuery->where('id', $cycleId);
        }

        // Apply date range filters
        if ($dateFrom) {
            $cycleExpenseQuery->where('expense_date', '>=', $dateFrom);
            $assocExpenseQuery->where('expense_date', '>=', $dateFrom);
            $saleQuery->where('sale_date', '>=', $dateFrom);
            $healthIncidentQuery->where('date_reported', '>=', $dateFrom);
            $auditQuery->where('created_at', '>=', $dateFrom);
            $resolutionQuery->where('created_at', '>=', $dateFrom);
            $withdrawalQuery->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $cycleExpenseQuery->where('expense_date', '<=', $dateTo);
            $assocExpenseQuery->where('expense_date', '<=', $dateTo);
            $saleQuery->where('sale_date', '<=', $dateTo);
            $healthIncidentQuery->where('date_reported', '<=', $dateTo);
            $auditQuery->where('created_at', '<=', $dateTo);
            $resolutionQuery->where('created_at', '<=', $dateTo);
            $withdrawalQuery->where('created_at', '<=', $dateTo);
        }

        // Apply pig-level filters
        if ($pigStatus) {
            $pigQuery->where('status', $pigStatus);
        }
        if ($pigSex) {
            $pigQuery->where('sex', $pigSex);
        }

        // Combined expense query for totals
        $allExpenseQuery = $cycleExpenseQuery;

        return [
            'last_updated' => now()->toIso8601String(),
            'filters' => $this->buildFilters(),
            'kpis' => $this->buildKpis($pigQuery, $allExpenseQuery, $assocExpenseQuery, $saleQuery, $resolutionQuery, $withdrawalQuery, $healthTaskQuery, $cycleQuery, $cycleId),
            'charts' => $this->buildCharts($pigQuery, $allExpenseQuery, $assocExpenseQuery, $saleQuery, $healthIncidentQuery, $healthTaskQuery, $cycleQuery, $cycleId),
            'tables' => $this->buildTables($resolutionQuery, $withdrawalQuery, $healthTaskQuery),
            'alerts' => $this->buildAlerts($pigQuery, $healthTaskQuery, $resolutionQuery, $allExpenseQuery),
            'recent_activity' => $this->buildRecentActivity($auditQuery),
        ];
    }

    /**
     * Build filter option lists for the dashboard filter bar.
     *
     * @return array<string, mixed>
     */
    private function buildFilters(): array
    {
        $cycles = PigCycle::query()
            ->activeRecords()
            ->select('id', 'batch_code', 'stage', 'status', 'caretaker_user_id')
            ->with('caretaker:id,name')
            ->get()
            ->map(fn (PigCycle $c) => [
                'id' => $c->id,
                'label' => $c->batch_code . ' (' . $c->stage . ')',
                'caretaker' => $c->caretaker?->name,
                'status' => $c->status,
            ])
            ->values()
            ->toArray();

        $caretakers = User::query()
            ->whereIn('id', PigCycle::query()->select('caretaker_user_id')->distinct()->pluck('caretaker_user_id')->filter())
            ->select('id', 'name')
            ->get()
            ->map(fn (User $u) => ['id' => $u->id, 'label' => $u->name])
            ->values()
            ->toArray();

        return [
            'cycles' => $cycles,
            'caretakers' => $caretakers,
            'pig_statuses' => Pig::STATUSES,
            'pig_sexes' => Pig::SEX_OPTIONS,
            'expense_categories' => array_values(array_unique(array_merge(
                PigCycleExpense::query()->distinct()->pluck('category')->filter()->toArray(),
                AssociationExpense::query()->distinct()->pluck('category')->filter()->toArray(),
            ))),
            'health_conditions' => CycleHealthIncident::query()
                ->distinct()
                ->pluck('incident_type')
                ->filter()
                ->values()
                ->toArray(),
            'resolution_statuses' => ['draft', 'pending_approval', 'approved', 'dswd_submitted', 'withdrawn', 'finalized'],
        ];
    }

    /**
     * Build KPI summary card data.
     */
    private function buildKpis(
        $pigQuery,
        $expenseQuery,
        $assocExpenseQuery,
        $saleQuery,
        $resolutionQuery,
        $withdrawalQuery,
        $healthTaskQuery,
        $cycleQuery,
        ?int $cycleId
    ): array {
        $pigCounts = (clone $pigQuery)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $totalPigs = array_sum($pigCounts);

        $cycleExpenseTotal = (clone $expenseQuery)->sum('amount') ?? 0;
        $assocExpenseTotal = (clone $assocExpenseQuery)->sum('amount') ?? 0;
        $totalExpenses = (float) $cycleExpenseTotal + (float) $assocExpenseTotal;

        $totalSales = (clone $saleQuery)->sum('amount') ?? 0;
        $totalCollected = (clone $saleQuery)->sum('amount_paid') ?? 0;

        $netProfit = ProfitabilitySnapshot::query()
            ->when($cycleId, fn ($q) => $q->where('pig_cycle_id', $cycleId))
            ->where('is_current', true)
            ->sum('net_profit_or_loss') ?? 0;

        return [
            'total_cycles' => (clone $cycleQuery)->count(),
            'active_cycles' => (clone $cycleQuery)->whereNotIn('status', PigCycle::ARCHIVED_STATUSES)->count(),
            'total_pigs' => $totalPigs,
            'piglets' => $pigCounts['Active'] ?? 0,
            'sick_pigs' => ($pigCounts['Sick'] ?? 0) + ($pigCounts['Isolated'] ?? 0),
            'deceased_pigs' => $pigCounts['Deceased'] ?? 0,
            'sold_pigs' => $pigCounts['Sold'] ?? 0,
            'total_expenses' => round($totalExpenses, 2),
            'total_sales' => round((float) $totalSales, 2),
            'collected_revenue' => round((float) $totalCollected, 2),
            'net_profit' => round((float) $netProfit, 2),
            'pending_resolutions' => (clone $resolutionQuery)
                ->whereIn('status', ['pending_approval', 'approved', 'dswd_submitted'])
                ->count(),
            'pending_withdrawals' => (clone $withdrawalQuery)->where('status', 'pending')->count(),
            'upcoming_treatments' => (clone $healthTaskQuery)
                ->where('status', 'pending')
                ->where('planned_start_date', '>=', today()->toDateString())
                ->count(),
            'overdue_treatments' => (clone $healthTaskQuery)
                ->where('status', 'pending')
                ->where('planned_start_date', '<', today()->toDateString())
                ->count(),
        ];
    }

    /**
     * Build chart datasets for the dashboard.
     */
    private function buildCharts(
        $pigQuery,
        $expenseQuery,
        $assocExpenseQuery,
        $saleQuery,
        $healthIncidentQuery,
        $healthTaskQuery,
        $cycleQuery,
        ?int $cycleId
    ): array {
        return [
            'pig_status_distribution' => $this->pigStatusDistribution($pigQuery),
            'pig_count_by_cycle' => $this->pigCountByCycle($cycleQuery),
            'expenses_by_category' => $this->expensesByCategory($expenseQuery, $assocExpenseQuery),
            'sales_vs_expenses_trend' => $this->salesVsExpensesTrend($expenseQuery, $assocExpenseQuery, $saleQuery),
            'net_profit_per_cycle' => $this->netProfitPerCycle($cycleId),
            'health_incidents_by_type' => $this->healthIncidentsByType($healthIncidentQuery),
            'mortality_trend' => $this->mortalityTrend($healthIncidentQuery),
            'treatment_completion' => $this->treatmentCompletion($healthTaskQuery),
            'pending_approvals_summary' => $this->pendingApprovalsSummary(),
        ];
    }

    private function pigStatusDistribution($pigQuery): array
    {
        $counts = (clone $pigQuery)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $allStatuses = Pig::STATUSES;

        return [
            'labels' => $allStatuses,
            'data' => array_map(fn ($s) => $counts[$s] ?? 0, $allStatuses),
            'colors' => ['#10b981', '#f59e0b', '#8b5cf6', '#3b82f6', '#ef4444'],
        ];
    }

    private function pigCountByCycle($cycleQuery): array
    {
        $cycles = (clone $cycleQuery)
            ->select('id', 'batch_code', 'current_count', 'initial_count', 'stage')
            ->orderBy('created_at')
            ->get();

        return [
            'labels' => $cycles->pluck('batch_code')->toArray(),
            'data' => $cycles->pluck('current_count')->toArray(),
            'stages' => $cycles->pluck('stage')->toArray(),
        ];
    }

    private function expensesByCategory($expenseQuery, $assocExpenseQuery): array
    {
        $cycleCategories = (clone $expenseQuery)
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        $assocCategories = (clone $assocExpenseQuery)
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        $merged = [];
        foreach ($cycleCategories as $cat => $val) {
            $merged[$cat] = ($merged[$cat] ?? 0) + (float) $val;
        }
        foreach ($assocCategories as $cat => $val) {
            $merged[$cat] = ($merged[$cat] ?? 0) + (float) $val;
        }

        arsort($merged);

        $colors = ['#10b981', '#0ea5e9', '#8b5cf6', '#f59e0b', '#64748b', '#06b6d4', '#ef4444', '#ec4899', '#84cc16', '#f97316'];

        return [
            'labels' => array_keys($merged),
            'data' => array_map(fn ($v) => round((float) $v, 2), array_values($merged)),
            'colors' => $colors,
        ];
    }

    private function salesVsExpensesTrend($expenseQuery, $assocExpenseQuery, $saleQuery): array
    {
        $months = [];
        $salesData = [];
        $expenseData = [];
        $profitData = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $label = $date->format('M Y');
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();

            $monthlyExpenses = (clone $expenseQuery)
                ->whereBetween('expense_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                ->sum('amount') ?? 0;

            $monthlyAssocExpenses = (clone $assocExpenseQuery)
                ->whereBetween('expense_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                ->sum('amount') ?? 0;

            $monthlySales = (clone $saleQuery)
                ->whereBetween('sale_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                ->sum('amount') ?? 0;

            $months[] = $label;
            $salesData[] = round((float) $monthlySales, 2);
            $expenseData[] = round((float) ($monthlyExpenses + $monthlyAssocExpenses), 2);
            $profitData[] = round((float) $monthlySales - (float) ($monthlyExpenses + $monthlyAssocExpenses), 2);
        }

        return [
            'labels' => $months,
            'sales' => $salesData,
            'expenses' => $expenseData,
            'profit' => $profitData,
        ];
    }

    private function netProfitPerCycle(?int $cycleId): array
    {
        $query = ProfitabilitySnapshot::query()->where('is_current', true);
        if ($cycleId) {
            $query->where('pig_cycle_id', $cycleId);
        }

        $snapshots = $query->with('cycle:id,batch_code')->orderBy('created_at')->get();

        return [
            'labels' => $snapshots->map(fn ($s) => $s->cycle?->batch_code ?? 'Unknown')->toArray(),
            'data' => $snapshots->pluck('net_profit_or_loss')->map(fn ($v) => round((float) $v, 2))->toArray(),
            'revenue' => $snapshots->pluck('gross_income')->map(fn ($v) => round((float) $v, 2))->toArray(),
            'expenses' => $snapshots->pluck('total_expenses')->map(fn ($v) => round((float) $v, 2))->toArray(),
        ];
    }

    private function healthIncidentsByType($healthIncidentQuery): array
    {
        $counts = (clone $healthIncidentQuery)
            ->select('incident_type', DB::raw('COUNT(*) as total'))
            ->groupBy('incident_type')
            ->pluck('total', 'incident_type')
            ->toArray();

        arsort($counts);

        return [
            'labels' => array_keys($counts),
            'data' => array_values($counts),
            'colors' => ['#f59e0b', '#ef4444', '#8b5cf6', '#0ea5e9', '#10b981', '#64748b'],
        ];
    }

    private function mortalityTrend($healthIncidentQuery): array
    {
        $months = [];
        $data = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $label = $date->format('M Y');
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();

            $count = (clone $healthIncidentQuery)
                ->where('incident_type', 'Death')
                ->whereBetween('date_reported', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                ->sum('affected_count') ?? 0;

            $months[] = $label;
            $data[] = (int) $count;
        }

        return [
            'labels' => $months,
            'data' => $data,
        ];
    }

    private function treatmentCompletion($healthTaskQuery): array
    {
        $total = (clone $healthTaskQuery)->count();
        $completed = (clone $healthTaskQuery)->where('status', 'completed')->count();
        $pending = (clone $healthTaskQuery)->where('status', 'pending')->count();
        $overdue = (clone $healthTaskQuery)
            ->where('status', 'pending')
            ->where('planned_start_date', '<', today()->toDateString())
            ->count();

        return [
            'labels' => ['Completed', 'Pending (On Track)', 'Overdue'],
            'data' => [$completed, max(0, $pending - $overdue), $overdue],
            'colors' => ['#10b981', '#f59e0b', '#ef4444'],
        ];
    }

    private function pendingApprovalsSummary(): array
    {
        return [
            'pending_approval' => Resolution::where('status', 'pending_approval')->count(),
            'ready_for_dswd' => Resolution::where('status', 'approved')->count(),
            'awaiting_dswd' => Resolution::where('status', 'dswd_submitted')->count(),
            'ready_for_withdrawal' => Resolution::where('status', 'withdrawn')->count(),
            'finalized' => Resolution::where('status', 'finalized')->count(),
        ];
    }

    /**
     * Build table data for pending resolutions, withdrawals, and upcoming treatments.
     */
    private function buildTables(
        $resolutionQuery,
        $withdrawalQuery,
        $healthTaskQuery
    ): array {
        $pendingResolutions = (clone $resolutionQuery)
            ->whereIn('status', ['pending_approval', 'approved', 'dswd_submitted'])
            ->with('meeting:id,title,date')
            ->latest()
            ->limit(8)
            ->get()
            ->map(function (Resolution $r) {
                return [
                    'id' => $r->id,
                    'number' => $r->resolution_number,
                    'title' => $r->title,
                    'status' => $r->status,
                    'focal_person' => $r->focal_person_name,
                    'age_days' => (int) $r->created_at->diffInDays(now()),
                    'meeting_title' => $r->meeting?->title,
                    'resolution_date' => $r->created_at->toDateString(),
                ];
            })
            ->toArray();

        $pendingWithdrawals = (clone $withdrawalQuery)
            ->where('status', 'pending')
            ->with('resolution:id,title,resolution_number')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function (Withdrawal $w) {
                return [
                    'id' => $w->id,
                    'amount' => round((float) $w->amount, 2),
                    'status' => $w->status,
                    'resolution_title' => $w->resolution?->title,
                    'resolution_number' => $w->resolution?->resolution_number,
                    'requested_at' => $w->requested_at?->toDateString(),
                ];
            })
            ->toArray();

        $upcomingTreatments = (clone $healthTaskQuery)
            ->where('status', 'pending')
            ->where('planned_start_date', '>=', today()->toDateString())
            ->with('cycle:id,batch_code')
            ->orderBy('planned_start_date')
            ->limit(8)
            ->get()
            ->map(function (CycleHealthTask $t) {
                return [
                    'id' => $t->id,
                    'task_name' => $t->task_name,
                    'task_type' => $t->task_type,
                    'batch_code' => $t->cycle?->batch_code,
                    'planned_start_date' => $t->planned_start_date instanceof Carbon
                        ? $t->planned_start_date->toDateString()
                        : $t->planned_start_date,
                ];
            })
            ->toArray();

        return [
            'pending_resolutions' => $pendingResolutions,
            'pending_withdrawals' => $pendingWithdrawals,
            'upcoming_treatments' => $upcomingTreatments,
        ];
    }

    /**
     * Build alert cards for actionable insights.
     */
    private function buildAlerts(
        $pigQuery,
        $healthTaskQuery,
        $resolutionQuery,
        $expenseQuery
    ): array {
        $alerts = [];

        $deceasedCount = (clone $pigQuery)->where('status', 'Deceased')->count();
        $totalPigs = (clone $pigQuery)->count();
        $mortalityRate = $totalPigs > 0 ? ($deceasedCount / $totalPigs) * 100 : 0;

        if ($mortalityRate > 5) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'warning',
                'title' => 'High Mortality Rate',
                'message' => sprintf(
                    'Mortality rate is at %.1f%% (%d deceased out of %d pigs). Immediate review recommended.',
                    $mortalityRate,
                    $deceasedCount,
                    $totalPigs
                ),
            ];
        }

        $overdueCount = (clone $healthTaskQuery)
            ->where('status', 'pending')
            ->where('planned_start_date', '<', today()->toDateString())
            ->count();

        if ($overdueCount > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'clock',
                'title' => 'Overdue Treatments',
                'message' => sprintf(
                    '%d health treatment(s) are past their scheduled date and still pending.',
                    $overdueCount
                ),
            ];
        }

        $staleResolutions = (clone $resolutionQuery)
            ->where('status', 'pending_approval')
            ->where('created_at', '<', now()->subDays(7))
            ->count();

        if ($staleResolutions > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'document',
                'title' => 'Stale Pending Approvals',
                'message' => sprintf(
                    '%d resolution(s) pending approval for more than 7 days.',
                    $staleResolutions
                ),
            ];
        }

        $currentMonthExpenses = (clone $expenseQuery)
            ->whereBetween('expense_date', [now()->startOfMonth()->toDateString(), now()->toDateString()])
            ->sum('amount') ?? 0;

        $avgLast3Months = (clone $expenseQuery)
            ->whereBetween('expense_date', [
                now()->subMonths(3)->startOfMonth()->toDateString(),
                now()->subMonth()->endOfMonth()->toDateString(),
            ])
            ->selectRaw('SUM(amount) / 3 as avg')
            ->value('avg') ?? 0;

        if ((float) $avgLast3Months > 0 && (float) $currentMonthExpenses > (float) $avgLast3Months * 1.5) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'chart',
                'title' => 'Unusual Expense Increase',
                'message' => 'Current month expenses are significantly higher than the 3-month average. Review expense items.',
            ];
        }

        return $alerts;
    }

    /**
     * Build recent activity feed from audit trails.
     */
    private function buildRecentActivity($auditQuery): array
    {
        return (clone $auditQuery)
            ->latest()
            ->limit(15)
            ->get()
            ->map(function (AuditTrail $log) {
                return [
                    'id' => $log->id,
                    'user' => $log->user?->name ?? 'System',
                    'action' => $log->action,
                    'module' => $log->module,
                    'description' => $log->description,
                    'created_at' => $log->created_at->diffForHumans(),
                    'timestamp' => $log->created_at->toIso8601String(),
                ];
            })
            ->toArray();
    }
}
