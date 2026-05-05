<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreAssociationExpenseRequest;
use App\Http\Requests\Expense\UpdateAssociationExpenseRequest;
use App\Models\AssociationExpense;
use App\Models\Canvass;
use App\Models\Resolution;
use App\Models\Supplier;
use App\Services\Expense\AssociationExpenseSummaryService;
use App\Services\Expense\CombinedExpenseService;
use App\Services\Expense\DeleteAssociationExpenseService;
use App\Services\Expense\RecordAssociationExpenseService;
use App\Services\Expense\UpdateAssociationExpenseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssociationExpenseController extends Controller
{
    use RecordsAuditTrail;

    public function index(
        Request $request,
        AssociationExpenseSummaryService $summaryService
    ): View|JsonResponse {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'category' => trim((string) $request->query('category', '')),
            'feed_subcategory' => trim((string) $request->query('feed_subcategory', '')),
            'fund_source' => trim((string) $request->query('fund_source', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
            'resolution_id' => trim((string) $request->query('resolution_id', '')),
        ];

        $query = AssociationExpense::query()->with([
            'supplier:id,name',
            'approvedResolution:id,title,resolution_number',
            'withdrawal:id,amount,status',
            'createdBy:id,name',
        ]);

        $this->applyFilters($query, $filters);

        $expenses = $query
            ->latest('expense_date')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $summary = $summaryService->buildFromQuery(clone $query);
        $summary['month_over_month'] = $summaryService->buildMonthComparison(
            $filters['resolution_id'] !== '' && ctype_digit($filters['resolution_id'])
                ? (int) $filters['resolution_id']
                : null
        );

        if ($request->wantsJson()) {
            return response()->json([
                'expenses' => collect($expenses->items())->map(function ($expense) {
                    return $this->serializeExpense($expense);
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

        return view('expenses.association.index', [
            'expenses' => $expenses,
            'summary' => $summary,
            'filters' => $filters,
            'categoryOptions' => AssociationExpense::categoryLabels(),
            'feedSubcategoryOptions' => AssociationExpense::feedSubcategoryLabels(),
            'fundSourceOptions' => AssociationExpense::fundSourceLabels(),
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'resolutions' => Resolution::query()->orderByDesc('created_at')->limit(50)->get(['id', 'title', 'resolution_number']),
        ]);
    }

    public function create(Request $request): View
    {
        return view('expenses.association.create', [
            'categoryOptions' => AssociationExpense::categoryLabels(),
            'feedSubcategoryOptions' => AssociationExpense::feedSubcategoryLabels(),
            'fundSourceOptions' => AssociationExpense::fundSourceLabels(),
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'canvasses' => Canvass::query()->orderByDesc('canvass_date')->limit(20)->get(['id', 'title']),
            'resolutions' => Resolution::query()
                ->whereIn('status', ['approved', 'withdrawn'])
                ->orderByDesc('created_at')
                ->limit(30)
                ->get(['id', 'title', 'resolution_number', 'status']),
        ]);
    }

    public function store(
        StoreAssociationExpenseRequest $request,
        RecordAssociationExpenseService $recordService
    ): RedirectResponse|JsonResponse {
        $expense = $recordService->handle($request->validated(), $request->user());

        $this->recordAudit(
            $request,
            'association_expense_created',
            "Recorded association expense #{$expense->id} ({$expense->category}) amounting to {$expense->amount}.",
            'expense_management',
            [
                'expense_id' => $expense->id,
                'category' => $expense->category,
                'amount' => (float) $expense->amount,
                'fund_source' => $expense->fund_source,
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Association expense saved successfully.',
                'expense' => $this->serializeExpense($expense),
                'redirect_url' => route('expenses.association.show', $expense),
            ], 201);
        }

        return redirect()
            ->route('expenses.association.show', $expense)
            ->with('status', 'Association expense saved successfully.');
    }

    public function show(AssociationExpense $expense): View
    {
        $expense->load([
            'supplier:id,name',
            'canvass:id,title',
            'approvedResolution:id,title,resolution_number',
            'withdrawal:id,amount,status',
            'createdBy:id,name',
            'updatedBy:id,name',
        ]);

        return view('expenses.association.show', [
            'expense' => $expense,
        ]);
    }

    public function edit(AssociationExpense $expense): View
    {
        $expense->load([
            'supplier:id,name',
            'canvass:id,title',
            'approvedResolution:id,title,resolution_number',
            'withdrawal:id,amount,status',
        ]);

        return view('expenses.association.edit', [
            'expense' => $expense,
            'categoryOptions' => AssociationExpense::categoryLabels(),
            'feedSubcategoryOptions' => AssociationExpense::feedSubcategoryLabels(),
            'fundSourceOptions' => AssociationExpense::fundSourceLabels(),
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'canvasses' => Canvass::query()->orderByDesc('canvass_date')->limit(20)->get(['id', 'title']),
            'resolutions' => Resolution::query()
                ->whereIn('status', ['approved', 'withdrawn'])
                ->orderByDesc('created_at')
                ->limit(30)
                ->get(['id', 'title', 'resolution_number', 'status']),
        ]);
    }

    public function update(
        UpdateAssociationExpenseRequest $request,
        AssociationExpense $expense,
        UpdateAssociationExpenseService $updateService
    ): RedirectResponse {
        $updatedExpense = $updateService->handle($expense, $request->validated(), $request->user());

        $this->recordAudit(
            $request,
            'association_expense_updated',
            "Updated association expense #{$updatedExpense->id}.",
            'expense_management',
            [
                'expense_id' => $updatedExpense->id,
                'category' => $updatedExpense->category,
                'amount' => (float) $updatedExpense->amount,
            ]
        );

        return redirect()
            ->route('expenses.association.show', $updatedExpense)
            ->with('status', 'Association expense updated successfully.');
    }

    public function destroy(
        Request $request,
        AssociationExpense $expense,
        DeleteAssociationExpenseService $deleteService
    ): RedirectResponse {
        $expenseId = $expense->id;
        $category = $expense->category;
        $amount = (float) $expense->amount;

        $deleteService->handle($expense);

        $this->recordAudit(
            $request,
            'association_expense_deleted',
            "Deleted association expense #{$expenseId}.",
            'expense_management',
            [
                'expense_id' => $expenseId,
                'category' => $category,
                'amount' => $amount,
            ]
        );

        return redirect()
            ->route('expenses.association.index')
            ->with('status', 'Association expense deleted successfully.');
    }

    /**
     * Combined "All Expenses" view merging cycle and association expenses.
     */
    public function all(Request $request, CombinedExpenseService $combinedService): View|JsonResponse
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'category' => trim((string) $request->query('category', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
        ];

        $expenses = $combinedService->paginated($filters);

        if ($request->wantsJson()) {
            return response()->json([
                'expenses' => $expenses->values(),
                'pagination' => [
                    'current_page' => $expenses->currentPage(),
                    'last_page' => $expenses->lastPage(),
                    'per_page' => $expenses->perPage(),
                    'total' => $expenses->total(),
                ],
            ]);
        }

        return view('expenses.all', [
            'expenses' => $expenses,
            'filters' => $filters,
            'categoryOptions' => AssociationExpense::categoryLabels(),
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
                    ->where('item_name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhere('receipt_reference', 'like', "%{$search}%");
            });
        }

        if ($filters['category'] !== '' && in_array($filters['category'], AssociationExpense::CATEGORIES, true)) {
            $query->where('category', $filters['category']);
        }

        if ($filters['feed_subcategory'] !== '' && in_array($filters['feed_subcategory'], AssociationExpense::FEED_SUBCATEGORIES, true)) {
            $query->where('feed_subcategory', $filters['feed_subcategory']);
        }

        if ($filters['fund_source'] !== '' && in_array($filters['fund_source'], AssociationExpense::FUND_SOURCES, true)) {
            $query->where('fund_source', $filters['fund_source']);
        }

        if ($filters['resolution_id'] !== '' && ctype_digit($filters['resolution_id'])) {
            $query->where('approved_resolution_id', (int) $filters['resolution_id']);
        }

        $validDateFrom = $filters['date_from'] !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_from']) === 1;
        $validDateTo = $filters['date_to'] !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_to']) === 1;

        if ($validDateFrom) {
            $query->whereDate('expense_date', '>=', $filters['date_from']);
        }

        if ($validDateTo) {
            $query->whereDate('expense_date', '<=', $filters['date_to']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeExpense(AssociationExpense $expense): array
    {
        return [
            'id' => $expense->id,
            'item_name' => $expense->item_name,
            'category' => $expense->category,
            'feed_subcategory' => $expense->feed_subcategory,
            'quantity' => $expense->quantity !== null ? (float) $expense->quantity : null,
            'unit' => $expense->unit,
            'unit_cost' => $expense->unit_cost !== null ? (float) $expense->unit_cost : null,
            'amount' => (float) $expense->amount,
            'expense_date' => $expense->expense_date?->toDateString(),
            'receipt_reference' => $expense->receipt_reference,
            'receipt_url' => $expense->receiptUrl(),
            'fund_source' => $expense->fund_source,
            'notes' => $expense->notes,
            'supplier' => $expense->supplier ? [
                'id' => $expense->supplier->id,
                'name' => $expense->supplier->name,
            ] : null,
            'canvass' => $expense->canvass ? [
                'id' => $expense->canvass->id,
                'title' => $expense->canvass->title,
            ] : null,
            'approved_resolution' => $expense->approvedResolution ? [
                'id' => $expense->approvedResolution->id,
                'title' => $expense->approvedResolution->title,
                'resolution_number' => $expense->approvedResolution->resolution_number,
            ] : null,
            'withdrawal' => $expense->withdrawal ? [
                'id' => $expense->withdrawal->id,
                'amount' => (float) $expense->withdrawal->amount,
                'status' => $expense->withdrawal->status,
            ] : null,
            'created_by_name' => $expense->createdBy?->name,
            'expense_scope' => 'association',
        ];
    }
}
