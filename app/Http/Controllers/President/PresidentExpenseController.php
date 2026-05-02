<?php

namespace App\Http\Controllers\President;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\PigRegistry\BulkDeletePigCycleExpenseRequest;
use App\Http\Requests\PigRegistry\DuplicatePigCycleExpenseRequest;
use App\Http\Requests\PigRegistry\StorePigCycleExpenseRequest;
use App\Http\Requests\PigRegistry\UpdateExpensePreferenceRequest;
use App\Http\Requests\PigRegistry\UpdatePigCycleExpenseRequest;
use App\Models\PigCycle;
use App\Models\PigCycleExpense;
use App\Services\PigRegistry\BulkDeleteExpenseService;
use App\Services\PigRegistry\DeletePigCycleExpenseService;
use App\Services\PigRegistry\DuplicateExpenseService;
use App\Services\PigRegistry\ExpensePreferenceService;
use App\Services\PigRegistry\ExpenseSummaryService;
use App\Services\PigRegistry\RecentExpenseTemplateService;
use App\Services\PigRegistry\RecordPigCycleExpenseService;
use App\Services\PigRegistry\UpdatePigCycleExpenseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PresidentExpenseController extends Controller
{
    use RecordsAuditTrail;

    public function index(Request $request, ExpenseSummaryService $expenseSummaryService): View|JsonResponse
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'category' => trim((string) $request->query('category', '')),
            'cycle_id' => trim((string) $request->query('cycle_id', '')),
            'month' => trim((string) $request->query('month', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
        ];

        $query = PigCycleExpense::query()->with([
            'cycle:id,batch_code,status,stage',
            'createdBy:id,name',
        ]);

        $this->applyFilters($query, $filters);

        $expenses = $query
            ->latest('expense_date')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $summary = $expenseSummaryService->buildFromQuery(clone $query);
        $summary['month_over_month'] = $expenseSummaryService->buildMonthComparison(
            $filters['cycle_id'] !== '' && ctype_digit($filters['cycle_id']) ? (int) $filters['cycle_id'] : null
        );

        if ($request->wantsJson()) {
            return response()->json([
                'expenses' => collect($expenses->items())->map(function ($expense) {
                    return [
                        'id' => $expense->id,
                        'batch_id' => $expense->batch_id,
                        'category' => $expense->category,
                        'quantity' => $expense->quantity !== null ? (float) $expense->quantity : null,
                        'unit' => $expense->unit,
                        'unit_cost' => $expense->unit_cost !== null ? (float) $expense->unit_cost : null,
                        'amount' => (float) $expense->amount,
                        'expense_date' => $expense->expense_date?->toDateString(),
                        'notes' => $expense->notes,
                        'receipt_url' => $expense->receiptUrl(),
                        'cycle' => $expense->cycle ? [
                            'id' => $expense->cycle->id,
                            'batch_code' => $expense->cycle->batch_code,
                            'status' => $expense->cycle->status,
                            'stage' => $expense->cycle->stage,
                            'isArchived' => $expense->cycle->isArchived(),
                        ] : null,
                        'created_by_name' => $expense->createdBy?->name,
                    ];
                })->values(),
                'summary' => $summary,
                'pagination' => [
                    'current_page' => $expenses->currentPage(),
                    'last_page' => $expenses->lastPage(),
                    'per_page' => $expenses->perPage(),
                    'total' => $expenses->total(),
                ],
            ]);
        }

        return view('expenses.index', [
            'expenses' => $expenses,
            'summary' => $summary,
            'filters' => $filters,
            'categoryOptions' => $this->categoryOptions(),
            'cycles' => PigCycle::query()->orderByDesc('updated_at')->get(['id', 'batch_code', 'status', 'stage']),
        ]);
    }

    public function summary(Request $request, ExpenseSummaryService $expenseSummaryService): View
    {
        $timeframe = trim((string) $request->query('timeframe', 'this_month'));
        if (! in_array($timeframe, ['this_month', 'last_month', 'all_time'], true)) {
            $timeframe = 'this_month';
        }

        $cycleId = trim((string) $request->query('cycle_id', ''));

        $query = PigCycleExpense::query()->with('cycle:id,batch_code,status,stage');
        $referenceDate = now();

        if ($cycleId !== '' && ctype_digit($cycleId)) {
            $query->where('batch_id', (int) $cycleId);
        }

        if ($timeframe === 'this_month') {
            $start = $referenceDate->copy()->startOfMonth()->startOfDay()->toDateTimeString();
            $end = $referenceDate->copy()->endOfMonth()->endOfDay()->toDateTimeString();
            $query->whereBetween('expense_date', [$start, $end]);
        }

        if ($timeframe === 'last_month') {
            $lastMonthReference = $referenceDate->copy()->subMonthNoOverflow();
            $start = $lastMonthReference->copy()->startOfMonth()->startOfDay()->toDateTimeString();
            $end = $lastMonthReference->copy()->endOfMonth()->endOfDay()->toDateTimeString();
            $query->whereBetween('expense_date', [$start, $end]);
        }

        $summary = $expenseSummaryService->buildFromQuery(clone $query);
        $summary['month_over_month'] = $expenseSummaryService->buildMonthComparison(
            $cycleId !== '' && ctype_digit($cycleId) ? (int) $cycleId : null
        );

        $recentExpenses = (clone $query)
            ->latest('expense_date')
            ->latest('id')
            ->limit(8)
            ->get(['id', 'batch_id', 'category', 'quantity', 'unit', 'unit_cost', 'amount', 'expense_date', 'notes']);

        return view('expenses.summary', [
            'summary' => $summary,
            'recentExpenses' => $recentExpenses,
            'timeframe' => $timeframe,
            'cycleId' => $cycleId,
            'categoryOptions' => $this->categoryOptions(),
            'cycles' => PigCycle::query()->orderByDesc('updated_at')->get(['id', 'batch_code', 'status', 'stage']),
        ]);
    }

    public function create(
        Request $request,
        ExpensePreferenceService $expensePreferenceService,
        RecentExpenseTemplateService $recentExpenseTemplateService
    ): View {
        $selectedCycle = trim((string) $request->query('cycle_id', ''));
        $preferences = $expensePreferenceService->defaultsFor($request->user());

        if ($selectedCycle === '' && ($preferences['last_cycle_id'] ?? 0) > 0) {
            $selectedCycle = (string) $preferences['last_cycle_id'];
        }

        return view('expenses.create', [
            'categoryOptions' => $this->categoryOptions(),
            'cycles' => PigCycle::query()->activeRecords()->orderByDesc('updated_at')->get(['id', 'batch_code', 'status', 'stage']),
            'selectedCycleId' => $selectedCycle,
            'preferences' => $preferences,
            'recentTemplates' => $recentExpenseTemplateService->forUser($request->user()),
        ]);
    }

    public function store(
        StorePigCycleExpenseRequest $request,
        RecordPigCycleExpenseService $recordPigCycleExpenseService,
        ExpensePreferenceService $expensePreferenceService
    ): RedirectResponse|JsonResponse {
        $expense = $recordPigCycleExpenseService->handle($request->validated(), $request->user());
        $preferences = $expensePreferenceService->rememberExpense($request->user(), $expense);

        $this->recordAudit(
            $request,
            'expense_created',
            "Recorded expense entry #{$expense->id} ({$expense->category}) amounting to {$expense->amount}.",
            'expense_management',
            [
                'expense_id' => $expense->id,
                'cycle_id' => $expense->batch_id,
                'category' => $expense->category,
                'amount' => (float) $expense->amount,
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Expense record saved successfully.',
                'expense' => [
                    'id' => $expense->id,
                    'batch_id' => $expense->batch_id,
                    'category' => $expense->category,
                    'quantity' => $expense->quantity !== null ? (float) $expense->quantity : null,
                    'unit' => $expense->unit,
                    'unit_cost' => $expense->unit_cost !== null ? (float) $expense->unit_cost : null,
                    'amount' => (float) $expense->amount,
                    'expense_date' => $expense->expense_date?->toDateString(),
                    'notes' => $expense->notes,
                    'receipt_url' => $expense->receiptUrl(),
                    'cycle' => $expense->cycle,
                    'created_by_name' => $expense->createdBy?->name,
                ],
                'preferences' => $preferences,
                'redirect_url' => route('expenses.show', $expense),
            ], 201);
        }

        if ($request->boolean('add_another')) {
            return redirect()
                ->route('expenses.create', ['cycle_id' => $expense->batch_id])
                ->with('status', 'Expense record saved. You can add another entry.');
        }

        return redirect()
            ->route('expenses.show', $expense)
            ->with('status', 'Expense record saved successfully.');
    }

    public function show(PigCycleExpense $expense): View
    {
        $expense->load([
            'cycle:id,batch_code,status,stage',
            'createdBy:id,name',
            'updatedBy:id,name',
        ]);

        return view('expenses.show', [
            'expense' => $expense,
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function edit(PigCycleExpense $expense): View|RedirectResponse
    {
        $expense->load('cycle:id,batch_code,status,stage');

        if ($expense->cycle?->isArchived()) {
            return redirect()
                ->route('expenses.show', $expense)
                ->withErrors([
                    'expense' => 'Expenses linked to archived cycles can no longer be edited.',
                ]);
        }

        $cycles = PigCycle::query()->activeRecords()->orderByDesc('updated_at')->get(['id', 'batch_code', 'status', 'stage']);

        if ($expense->cycle instanceof PigCycle && ! $cycles->contains('id', $expense->cycle->id)) {
            $cycles->prepend($expense->cycle);
        }

        return view('expenses.edit', [
            'expense' => $expense,
            'categoryOptions' => $this->categoryOptions(),
            'cycles' => $cycles,
        ]);
    }

    public function update(
        UpdatePigCycleExpenseRequest $request,
        PigCycleExpense $expense,
        UpdatePigCycleExpenseService $updatePigCycleExpenseService
    ): RedirectResponse {
        $updatedExpense = $updatePigCycleExpenseService->handle($expense, $request->validated(), $request->user());

        $this->recordAudit(
            $request,
            'expense_updated',
            "Updated expense entry #{$updatedExpense->id}.",
            'expense_management',
            [
                'expense_id' => $updatedExpense->id,
                'cycle_id' => $updatedExpense->batch_id,
                'category' => $updatedExpense->category,
                'amount' => (float) $updatedExpense->amount,
            ]
        );

        return redirect()
            ->route('expenses.show', $updatedExpense)
            ->with('status', 'Expense record updated successfully.');
    }

    public function destroy(
        Request $request,
        PigCycleExpense $expense,
        DeletePigCycleExpenseService $deletePigCycleExpenseService
    ): RedirectResponse {
        if (! $request->user()?->hasRole('president')) {
            abort(403, 'Only the president can delete expense records.');
        }

        $expenseId = $expense->id;
        $cycleId = $expense->batch_id;
        $category = $expense->category;
        $amount = (float) $expense->amount;

        try {
            $deletePigCycleExpenseService->handle($expense);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        $this->recordAudit(
            $request,
            'expense_deleted',
            "Deleted expense entry #{$expenseId}.",
            'expense_management',
            [
                'expense_id' => $expenseId,
                'cycle_id' => $cycleId,
                'category' => $category,
                'amount' => $amount,
            ]
        );

        return redirect()
            ->route('expenses.index')
            ->with('status', 'Expense record deleted successfully.');
    }

    public function duplicate(
        DuplicatePigCycleExpenseRequest $request,
        PigCycleExpense $expense,
        DuplicateExpenseService $duplicateExpenseService
    ): RedirectResponse|JsonResponse {
        $newExpense = $duplicateExpenseService->handle(
            $expense,
            $request->validated(),
            $request->user()
        );

        $this->recordAudit(
            $request,
            'expense_duplicated',
            "Duplicated expense entry #{$expense->id} to #{$newExpense->id}.",
            'expense_management',
            [
                'original_expense_id' => $expense->id,
                'new_expense_id' => $newExpense->id,
                'cycle_id' => $newExpense->batch_id,
                'category' => $newExpense->category,
                'amount' => (float) $newExpense->amount,
            ]
        );

        $redirectUrl = route('expenses.show', $newExpense);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Expense record duplicated successfully.',
                'redirect_url' => $redirectUrl,
            ]);
        }

        return redirect($redirectUrl)->with('status', 'Expense record duplicated successfully.');
    }

    public function bulkDelete(
        BulkDeletePigCycleExpenseRequest $request,
        BulkDeleteExpenseService $bulkDeleteExpenseService
    ): RedirectResponse|JsonResponse {
        $ids = $request->validated('ids');

        $expenses = PigCycleExpense::query()
            ->whereIn('id', $ids)
            ->get();

        try {
            $deletedCount = $bulkDeleteExpenseService->handle($expenses);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        $this->recordAudit(
            $request,
            'expense_bulk_deleted',
            "Bulk deleted {$deletedCount} expense record(s).",
            'expense_management',
            [
                'deleted_count' => $deletedCount,
                'expense_ids' => $ids,
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "{$deletedCount} expense record(s) deleted successfully.",
                'redirect_url' => route('expenses.index'),
            ]);
        }

        return redirect()
            ->route('expenses.index')
            ->with('status', "{$deletedCount} expense record(s) deleted successfully.");
    }

    public function preferences(Request $request, ExpensePreferenceService $expensePreferenceService): JsonResponse
    {
        return response()->json([
            'preferences' => $expensePreferenceService->defaultsFor($request->user()),
        ]);
    }

    public function updatePreferences(
        UpdateExpensePreferenceRequest $request,
        ExpensePreferenceService $expensePreferenceService
    ): JsonResponse {
        return response()->json([
            'preferences' => $expensePreferenceService->update($request->user(), $request->validated()),
        ]);
    }

    public function recentTemplates(
        Request $request,
        RecentExpenseTemplateService $recentExpenseTemplateService
    ): JsonResponse {
        return response()->json([
            'templates' => $recentExpenseTemplateService->forUser($request->user()),
        ]);
    }

    /**
     * @param  array<string, string>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if ($filters['search'] !== '') {
            $search = $filters['search'];

            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('notes', 'like', "%{$search}%")
                    ->orWhereHas('cycle', function (Builder $cycleQuery) use ($search): void {
                        $cycleQuery->where('batch_code', 'like', "%{$search}%");
                    });
            });
        }

        if ($filters['category'] !== '' && in_array($filters['category'], PigCycleExpense::CATEGORIES, true)) {
            $query->where('category', $filters['category']);
        }

        if ($filters['cycle_id'] !== '' && ctype_digit($filters['cycle_id'])) {
            $query->where('batch_id', (int) $filters['cycle_id']);
        }

        $hasMonth = $filters['month'] !== '' && preg_match('/^\d{4}-\d{2}$/', $filters['month']) === 1;

        if ($hasMonth) {
            try {
                $month = Carbon::createFromFormat('Y-m-d', $filters['month'].'-01');

                if ($month instanceof Carbon) {
                    $query->whereBetween('expense_date', [
                        $month->copy()->startOfMonth()->toDateString(),
                        $month->copy()->endOfMonth()->toDateString(),
                    ]);
                }
            } catch (\Throwable) {
                // Ignore invalid month filter values.
            }

            return;
        }

        $validDateFrom = $filters['date_from'] !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_from']) === 1;
        $validDateTo = $filters['date_to'] !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_to']) === 1;
        $dateFrom = $filters['date_from'];
        $dateTo = $filters['date_to'];

        if ($validDateFrom && $validDateTo && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        if ($validDateFrom || $validDateTo) {
            if ($validDateFrom) {
                $query->whereDate('expense_date', '>=', $dateFrom);
            }

            if ($validDateTo) {
                $query->whereDate('expense_date', '<=', $dateTo);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function categoryOptions(): array
    {
        return PigCycleExpense::categoryLabels();
    }
}
