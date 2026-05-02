<?php

namespace App\Http\Controllers\President;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\PigRegistry\FinalizeCycleProfitabilityRequest;
use App\Models\PigCycle;
use App\Services\PigRegistry\ComputeCycleProfitabilityService;
use App\Services\PigRegistry\FinalizeCycleProfitabilitySnapshotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PresidentProfitabilityController extends Controller
{
    use RecordsAuditTrail;

    public function __construct(
        private readonly ComputeCycleProfitabilityService $computeCycleProfitabilityService
    ) {}

    public function index(Request $request): View
    {
        $cycles = PigCycle::query()
            ->with(['caretaker:id,name', 'profitabilitySnapshot.finalizedBy:id,name'])
            ->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString();

        $cycleSummaries = $cycles->getCollection()
            ->mapWithKeys(fn (PigCycle $cycle): array => [
                $cycle->id => $this->profitabilityFor($cycle),
            ]);

        $allCycleSummaries = PigCycle::query()
            ->with('profitabilitySnapshot.finalizedBy:id,name')
            ->get()
            ->map(fn (PigCycle $cycle): array => $this->profitabilityFor($cycle));

        return view('profitability.index', [
            'cycles' => $cycles,
            'cycleSummaries' => $cycleSummaries,
            'summary' => [
                'total_sales' => round((float) $allCycleSummaries->sum('total_sales'), 2),
                'total_expenses' => round((float) $allCycleSummaries->sum('total_expenses'), 2),
                'net_profit_or_loss' => round((float) $allCycleSummaries->sum('net_profit_or_loss'), 2),
                'finalized_count' => $allCycleSummaries->where('is_finalized', true)->count(),
                'cycles_count' => $allCycleSummaries->count(),
            ],
        ]);
    }

    public function show(PigCycle $cycle): View
    {
        $cycle->load(['caretaker:id,name', 'profitabilitySnapshot.finalizedBy:id,name']);

        return view('profitability.show', [
            'cycle' => $cycle,
            'profitability' => $this->profitabilityFor($cycle),
            'snapshot' => $cycle->profitabilitySnapshot,
            'canFinalize' => request()->user()?->hasRole('president') && $cycle->isArchived() && $cycle->profitabilitySnapshot === null,
        ]);
    }

    public function sharing(PigCycle $cycle): View
    {
        $cycle->load(['caretaker:id,name', 'profitabilitySnapshot.finalizedBy:id,name']);

        return view('profitability.sharing', [
            'cycle' => $cycle,
            'profitability' => $this->profitabilityFor($cycle),
            'snapshot' => $cycle->profitabilitySnapshot,
            'canFinalize' => request()->user()?->hasRole('president') && $cycle->isArchived() && $cycle->profitabilitySnapshot === null,
        ]);
    }

    public function finalize(
        FinalizeCycleProfitabilityRequest $request,
        PigCycle $cycle,
        FinalizeCycleProfitabilitySnapshotService $finalizeCycleProfitabilitySnapshotService
    ): RedirectResponse {
        $validated = $request->validated();

        $snapshot = $finalizeCycleProfitabilitySnapshotService->handle(
            $cycle,
            $request->user(),
            $validated['notes'] ?? null
        );

        $this->recordAudit(
            $request,
            'profitability_finalized',
            "Finalized profitability snapshot for cycle {$cycle->batch_code}.",
            'profitability',
            [
                'cycle_id' => $cycle->id,
                'snapshot_id' => $snapshot->id,
            ]
        );

        return redirect()
            ->route('profitability.sharing', $cycle)
            ->with('status', 'Profitability snapshot was finalized for reports and resolutions.');
    }

    /**
     * @return array<string, mixed>
     */
    private function profitabilityFor(PigCycle $cycle): array
    {
        if ($cycle->relationLoaded('profitabilitySnapshot') && $cycle->profitabilitySnapshot !== null) {
            return $cycle->profitabilitySnapshot->toProfitabilitySummary();
        }

        return $this->computeCycleProfitabilityService->handle($cycle);
    }
}
