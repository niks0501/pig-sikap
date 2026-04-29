<?php

namespace App\Http\Controllers\President;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\PigRegistry\StorePigCycleSaleRequest;
use App\Http\Requests\PigRegistry\UpdatePigCycleSaleRequest;
use App\Models\PigBuyer;
use App\Models\PigCycle;
use App\Models\PigCycleSale;
use App\Services\PigRegistry\RecordPigCycleSaleService;
use App\Services\PigRegistry\SalesSummaryService;
use App\Services\PigRegistry\UpdatePigCycleSalePaymentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PresidentPigCycleSaleController extends Controller
{
    use RecordsAuditTrail;

    public function index(Request $request, SalesSummaryService $salesSummaryService): View
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'payment_status' => trim((string) $request->query('payment_status', '')),
            'sale_method' => trim((string) $request->query('sale_method', '')),
            'cycle_id' => trim((string) $request->query('cycle_id', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
        ];

        $query = PigCycleSale::query()->with([
            'cycle:id,batch_code,status,stage,current_count',
            'buyer:id,name,contact_number,address',
            'createdBy:id,name',
        ]);

        $this->applyFilters($query, $filters);

        $sales = $query
            ->latest('sale_date')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $summary = $salesSummaryService->buildFromQuery(clone $query);

        return view('sales.index', [
            'sales' => $sales,
            'summary' => $summary,
            'filters' => $filters,
            'cycles' => PigCycle::query()->orderByDesc('updated_at')->get(['id', 'batch_code', 'status', 'stage', 'current_count']),
            'paymentStatusOptions' => PigCycleSale::PAYMENT_STATUSES,
            'saleMethodOptions' => PigCycleSale::SALE_METHODS,
        ]);
    }

    public function create(Request $request): View
    {
        $selectedCycle = trim((string) $request->query('cycle_id', ''));

        return view('sales.create', [
            'cycles' => PigCycle::query()->activeRecords()->orderByDesc('updated_at')->get(['id', 'batch_code', 'status', 'stage', 'current_count']),
            'buyers' => PigBuyer::query()->orderBy('name')->get(['id', 'name', 'contact_number', 'address']),
            'selectedCycleId' => $selectedCycle,
            'paymentStatusOptions' => PigCycleSale::PAYMENT_STATUSES,
            'saleMethodOptions' => PigCycleSale::SALE_METHODS,
        ]);
    }

    public function store(
        StorePigCycleSaleRequest $request,
        RecordPigCycleSaleService $recordPigCycleSaleService
    ): RedirectResponse|JsonResponse {
        $sale = $recordPigCycleSaleService->handle($request->validated(), $request->user());

        $buyerName = $sale->buyer?->name ?? 'buyer';

        $this->recordAudit(
            $request,
            'sale_created',
            "Recorded sale #{$sale->id} for {$buyerName} amounting to {$sale->amount}.",
            'sales_management',
            [
                'sale_id' => $sale->id,
                'cycle_id' => $sale->batch_id,
                'buyer_id' => $sale->buyer_id,
                'amount' => (float) $sale->amount,
                'payment_status' => $sale->payment_status,
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Sale record saved successfully.',
                'sale' => $this->salePayload($sale),
                'redirect_url' => route('sales.show', $sale),
            ], 201);
        }

        if ($request->boolean('add_another')) {
            return redirect()
                ->route('sales.create', ['cycle_id' => $sale->batch_id])
                ->with('status', 'Sale record saved. You can add another entry.');
        }

        return redirect()
            ->route('sales.show', $sale)
            ->with('status', 'Sale record saved successfully.');
    }

    public function show(PigCycleSale $sale): View
    {
        $sale->load([
            'cycle:id,batch_code,status,stage,current_count',
            'buyer:id,name,contact_number,address,notes',
            'createdBy:id,name',
            'updatedBy:id,name',
        ]);

        $userRole = request()->user()?->role?->slug;

        return view('sales.show', [
            'sale' => $sale,
            'canEditPayment' => in_array($userRole, ['president', 'treasurer'], true),
            'canEditReceipt' => in_array($userRole, ['president', 'treasurer', 'secretary'], true),
        ]);
    }

    public function update(
        UpdatePigCycleSaleRequest $request,
        PigCycleSale $sale,
        UpdatePigCycleSalePaymentService $updatePigCycleSalePaymentService
    ): RedirectResponse|JsonResponse {
        $updatedSale = $updatePigCycleSalePaymentService->handle($sale, $request->validated(), $request->user());

        $this->recordAudit(
            $request,
            'sale_payment_updated',
            "Updated payment details for sale #{$updatedSale->id}.",
            'sales_management',
            [
                'sale_id' => $updatedSale->id,
                'cycle_id' => $updatedSale->batch_id,
                'amount' => (float) $updatedSale->amount,
                'amount_paid' => (float) $updatedSale->amount_paid,
                'payment_status' => $updatedSale->payment_status,
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Sale updated successfully.',
                'sale' => $this->salePayload($updatedSale),
            ]);
        }

        return redirect()
            ->route('sales.show', $updatedSale)
            ->with('status', 'Sale updated successfully.');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if ($filters['search'] !== '') {
            $search = $filters['search'];

            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->whereHas('buyer', function (Builder $buyerQuery) use ($search): void {
                        $buyerQuery->where('name', 'like', '%'.$search.'%')
                            ->orWhere('contact_number', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('cycle', function (Builder $cycleQuery) use ($search): void {
                        $cycleQuery->where('batch_code', 'like', '%'.$search.'%');
                    })
                    ->orWhere('receipt_reference', 'like', '%'.$search.'%');
            });
        }

        if ($filters['payment_status'] !== '' && in_array($filters['payment_status'], PigCycleSale::PAYMENT_STATUSES, true)) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if ($filters['sale_method'] !== '' && in_array($filters['sale_method'], PigCycleSale::SALE_METHODS, true)) {
            $query->where('sale_method', $filters['sale_method']);
        }

        if ($filters['cycle_id'] !== '' && ctype_digit($filters['cycle_id'])) {
            $query->where('batch_id', (int) $filters['cycle_id']);
        }

        if ($filters['date_from'] !== '' && $filters['date_to'] !== '') {
            $query->whereBetween('sale_date', [$filters['date_from'], $filters['date_to']]);
        } elseif ($filters['date_from'] !== '') {
            $query->whereDate('sale_date', '>=', $filters['date_from']);
        } elseif ($filters['date_to'] !== '') {
            $query->whereDate('sale_date', '<=', $filters['date_to']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function salePayload(PigCycleSale $sale): array
    {
        $saleDate = $sale->sale_date;

        return [
            'id' => $sale->id,
            'batch_id' => $sale->batch_id,
            'buyer_id' => $sale->buyer_id,
            'pigs_sold' => $sale->pigs_sold,
            'sale_method' => $sale->sale_method,
            'live_weight_kg' => $sale->live_weight_kg,
            'price_per_kg' => $sale->price_per_kg,
            'price_per_head' => $sale->price_per_head,
            'amount' => (float) $sale->amount,
            'amount_paid' => (float) $sale->amount_paid,
            'payment_status' => $sale->payment_status,
            'sale_date' => $saleDate instanceof Carbon ? $saleDate->toDateString() : null,
            'receipt_reference' => $sale->receipt_reference,
            'receipt_url' => $sale->receiptUrl(),
            'notes' => $sale->notes,
            'buyer' => $sale->buyer ? [
                'id' => $sale->buyer->id,
                'name' => $sale->buyer->name,
                'contact_number' => $sale->buyer->contact_number,
                'address' => $sale->buyer->address,
                'notes' => $sale->buyer->notes,
            ] : null,
            'cycle' => $sale->cycle ? [
                'id' => $sale->cycle->id,
                'batch_code' => $sale->cycle->batch_code,
                'status' => $sale->cycle->status,
                'stage' => $sale->cycle->stage,
                'current_count' => $sale->cycle->current_count,
            ] : null,
            'created_by_name' => $sale->createdBy?->name,
            'updated_by_name' => $sale->updatedBy?->name,
        ];
    }
}
