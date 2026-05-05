<?php

namespace App\Services\Expense;

use App\Models\AssociationExpense;
use App\Models\PigCycleExpense;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Merges cycle expenses and association expenses into a single
 * combined view for the "All Expenses" tab.
 */
class CombinedExpenseService
{
    /**
     * Fetch combined expenses from both tables sorted by date descending.
     *
     * @param  array<string, string>  $filters
     * @return LengthAwarePaginator
     */
    public function paginated(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $cycleQuery = PigCycleExpense::query()
            ->with(['cycle:id,batch_code,status,stage', 'createdBy:id,name', 'supplier:id,name'])
            ->select([
                'id', 'batch_id', 'withdrawal_id', 'category', 'feed_subcategory',
                'quantity', 'unit', 'unit_cost', 'amount', 'expense_date',
                'notes', 'receipt_path', 'receipt_reference', 'supplier_id',
                'created_by', 'created_at', 'updated_at',
            ])
            ->selectRaw("'cycle' as expense_scope");

        $associationQuery = AssociationExpense::query()
            ->with(['supplier:id,name', 'approvedResolution:id,title,resolution_number', 'createdBy:id,name'])
            ->select([
                'id', 'category', 'feed_subcategory', 'quantity', 'unit',
                'unit_cost', 'amount', 'expense_date', 'notes', 'receipt_path',
                'receipt_reference', 'supplier_id', 'created_by', 'created_at', 'updated_at',
            ])
            ->selectRaw("'association' as expense_scope");

        $this->applyFilters($cycleQuery, $filters);
        $this->applyFilters($associationQuery, $filters);

        $cycleResults = $cycleQuery->get();
        $associationResults = $associationQuery->get();

        $combined = $cycleResults->concat($associationResults)
            ->sortByDesc('expense_date')
            ->values();

        $total = $combined->count();
        $page = request()->integer('page', 1);
        $sliced = $combined->forPage($page, $perPage);

        return new LengthAwarePaginator(
            $sliced->values(),
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array<string, string>  $filters
     */
    private function applyFilters($query, array $filters): void
    {
        if (($filters['category'] ?? '') !== '') {
            $query->where('category', $filters['category']);
        }

        if (($filters['date_from'] ?? '') !== '') {
            $query->whereDate('expense_date', '>=', $filters['date_from']);
        }

        if (($filters['date_to'] ?? '') !== '') {
            $query->whereDate('expense_date', '<=', $filters['date_to']);
        }

        if (($filters['search'] ?? '') !== '') {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search): void {
                $builder->where('notes', 'like', "%{$search}%");
            });
        }
    }
}
