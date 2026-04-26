<?php

namespace App\Http\Controllers\President;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\PigRegistry\BulkDeletePigCycleExpenseRequest;
use App\Http\Requests\PigRegistry\DuplicatePigCycleExpenseRequest;
use App\Http\Requests\PigRegistry\StorePigCycleExpenseRequest;
use App\Http\Requests\PigRegistry\UpdatePigCycleExpenseRequest;
use App\Models\PigCycle;
use App\Models\PigCycleExpense;
use App\Services\PigRegistry\BulkDeleteExpenseService;
use App\Services\PigRegistry\DeletePigCycleExpenseService;
use App\Services\PigRegistry\DuplicateExpenseService;
use App\Services\PigRegistry\ExpenseSummaryService;
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

    public function index(Request $request, ExpenseSummaryService $expenseSummaryService): View
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

        if ($cycleId !== '' && ctype_digit($cycleId)) {
            $query->where('batch_id', (int) $cycleId);
        }

        if ($timeframe === 'this_month') {
            $start = now()->startOfMonth()->toDateString();
            $end = now()->endOfMonth()->toDateString();
            $query->whereBetween('expense_date', [$start, $end]);
        }

        if ($timeframe === 'last_month') {
            $start = now()->subMonthNoOverflow()->startOfMonth()->toDateString();
            $end = now()->subMonthNoOverflow()->endOfMonth()->toDateString();
            $query->whereBetween('expense_date', [$start, $end]);
        }

        $summary = $expenseSummaryService->buildFromQuery(clone $query);

        $recentExpenses = (clone $query)
            ->latest('expense_date')
            ->latest('id')
            ->limit(8)
            ->get(['id', 'batch_id', 'category', 'amount', 'expense_date', 'notes']);

        return view('expenses.summary', [
            'summary' => $summary,
            'recentExpenses' => $recentExpenses,
            'timeframe' => $timeframe,
            'cycleId' => $cycleId,
            'categoryOptions' => $this->categoryOptions(),
            'cycles' => PigCycle::query()->orderByDesc('updated_at')->get(['id', 'batch_code', 'status', 'stage']),
        ]);
    }

    public function create(Request $request): View
    {
        $selectedCycle = trim((string) $request->query('cycle_id', ''));

        return view('expenses.create', [
            'categoryOptions' => $this->categoryOptions(),
            'cycles' => PigCycle::query()->activeRecords()->orderByDesc('updated_at')->get(['id', 'batch_code', 'status', 'stage']),
            'selectedCycleId' => $selectedCycle,
        ]);
    }

    public function store(
        StorePigCycleExpenseRequest $request,
        RecordPigCycleExpenseService $recordPigCycleExpenseService
    ): RedirectResponse {
        $expense = $recordPigCycleExpenseService->handle($request->validated(), $request->user());

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

        if ($filters['month'] !== '' && preg_match('/^\d{4}-\d{2}$/', $filters['month']) === 1) {
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
        }

        if ($filters['date_from'] !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_from']) === 1) {
            $query->whereDate('expense_date', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_to']) === 1) {
            $query->whereDate('expense_date', '<=', $filters['date_to']);
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
