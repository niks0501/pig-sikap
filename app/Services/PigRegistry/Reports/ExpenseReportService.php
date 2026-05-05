<?php

namespace App\Services\PigRegistry\Reports;

use App\Models\PigCycleExpense;
use App\Services\PigRegistry\ExpenseSummaryService;

class ExpenseReportService
{
    public function __construct(private readonly ExpenseSummaryService $summaryService)
    {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function generate(array $filters): array
    {
        $query = PigCycleExpense::query()->with(['cycle:id,batch_code', 'createdBy:id,name']);

        if (! empty($filters['cycle_id'])) {
            $query->where('batch_id', $filters['cycle_id']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('expense_date', [$filters['start_date'], $filters['end_date']]);
        }

        $rows = $query->orderByDesc('expense_date')->get();
        $summary = $this->summaryService->buildFromQuery($query);

        $byCategory = $rows->groupBy('category')->map(fn ($items) => round((float) $items->sum('amount'), 2));

        $categoryCount = $byCategory->count();
        $greenPalette = ['#0c6d57', '#1e8a6d', '#3ca88d', '#5dc6a2', '#8edcbc', '#0a5a48', '#156e4e', '#2d8a6e', '#4bb892', '#6edcb2'];
        $pieColors = array_slice($greenPalette, 0, max($categoryCount, 1));

        return [
            'summary' => $summary,
            'rows' => $rows->map(fn (PigCycleExpense $expense): array => [
                'expense_date' => $expense->expense_date?->format('M d, Y'),
                'cycle_code' => $expense->cycle?->batch_code,
                'category' => $expense->categoryLabel(),
                'amount' => (float) $expense->amount,
                'notes' => $expense->notes,
                'recorded_by' => $expense->createdBy?->name,
            ])->values()->all(),
            'charts' => [
                'expensePie' => [
                    'labels' => $byCategory->keys()->map(fn ($c) => \App\Models\PigCycleExpense::categoryLabels()[$c] ?? ucfirst((string) $c))->values()->all(),
                    'datasets' => [[
                        'data' => $byCategory->values()->all(),
                        'backgroundColor' => $pieColors,
                    ]],
                ],
            ],
            'empty' => $rows->isEmpty(),
        ];
    }
}
