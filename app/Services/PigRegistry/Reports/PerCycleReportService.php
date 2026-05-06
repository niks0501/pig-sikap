<?php

namespace App\Services\PigRegistry\Reports;

use App\Models\PigCycle;
use App\Models\PigCycleExpense;
use App\Models\PigCycleSale;
use App\Services\PigRegistry\ComputeCycleProfitabilityService;

class PerCycleReportService
{
    public function __construct(
        private readonly ComputeCycleProfitabilityService $computeService,
    ) {}

    /**
     * Generate a comprehensive per-cycle report combining inventory,
     * expenses, sales, health, and profitability into one document.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function generate(array $filters): array
    {
        $cycle = PigCycle::query()
            ->with(['caretaker:id,name', 'profitabilitySnapshot'])
            ->findOrFail($filters['cycle_id']);

        $profitability = $cycle->profitabilitySnapshot?->toProfitabilitySummary()
            ?? $this->computeService->compute($cycle);

        $expenseRows = PigCycleExpense::query()
            ->where('batch_id', $cycle->id)
            ->orderByDesc('expense_date')
            ->get()
            ->map(fn (PigCycleExpense $e): array => [
                'expense_date' => $e->expense_date?->format('M d, Y'),
                'category' => PigCycleExpense::categoryLabels()[$e->category] ?? ucfirst((string) $e->category),
                'item_name' => $e->item_name,
                'quantity' => (int) $e->quantity,
                'unit' => $e->unit,
                'unit_cost' => round((float) $e->unit_cost, 2),
                'amount' => round((float) $e->amount, 2),
                'notes' => $e->notes,
            ])
            ->values()
            ->all();

        $expenseByCategory = collect($expenseRows)
            ->groupBy('category')
            ->map(fn ($group) => round($group->sum('amount'), 2))
            ->all();

        $salesRows = PigCycleSale::query()
            ->where('batch_id', $cycle->id)
            ->orderByDesc('sale_date')
            ->get()
            ->map(fn (PigCycleSale $s): array => [
                'sale_date' => $s->sale_date?->format('M d, Y'),
                'buyer' => $s->buyer?->name ?? 'Walk-in Buyer',
                'pigs_sold' => (int) $s->pigs_sold,
                'amount' => round((float) $s->amount, 2),
                'amount_paid' => round((float) $s->amount_paid, 2),
                'payment_status' => ucfirst((string) $s->payment_status),
            ])
            ->values()
            ->all();

        $healthIncidents = $cycle->healthIncidents()
            ->orderByDesc('date_reported')
            ->get()
            ->map(fn ($incident): array => [
                'date_reported' => $incident->date_reported?->format('M d, Y'),
                'incident_type' => ucfirst((string) ($incident->incident_type ?? '')),
                'affected_count' => (int) ($incident->affected_count ?? 0),
                'suspected_cause' => $incident->suspected_cause,
                'remarks' => $incident->remarks,
            ])
            ->values()
            ->all();

        $totalExpenses = round((float) collect($expenseRows)->sum('amount'), 2);
        $totalSales = round((float) ($profitability['total_sales'] ?? 0), 2);
        $totalCollected = round((float) ($profitability['total_collected'] ?? 0), 2);
        $receivables = round($totalSales - $totalCollected, 2);

        return [
            'summary' => [
                'cycle_code' => $cycle->batch_code,
                'stage' => $cycle->stage,
                'status' => $cycle->status,
                'caretaker' => $cycle->caretaker?->name,
                'date_of_purchase' => $cycle->date_of_purchase?->format('M d, Y'),
                'initial_count' => (int) $cycle->initial_count,
                'current_count' => (int) $cycle->current_count,
                'total_sales' => $totalSales,
                'total_collected' => $totalCollected,
                'receivables' => $receivables,
                'total_expenses' => $totalExpenses,
                'net_result' => round($totalSales - $totalExpenses, 2),
                'caretaker_share' => $profitability['caretaker_share'] ?? 0,
                'member_share' => $profitability['member_share'] ?? 0,
                'association_share' => $profitability['association_share'] ?? 0,
            ],
            'expense_rows' => $expenseRows,
            'expense_by_category' => $expenseByCategory,
            'sales_rows' => $salesRows,
            'health_incidents' => $healthIncidents,
            'charts' => [
                'expenseByCategory' => [
                    'labels' => array_keys($expenseByCategory),
                    'datasets' => [[
                        'data' => array_values($expenseByCategory),
                        'backgroundColor' => ['#0c6d57', '#1e8a6d', '#3ca88d', '#5dc6a2', '#8edcbc'],
                    ]],
                ],
            ],
            'empty' => false,
        ];
    }
}
