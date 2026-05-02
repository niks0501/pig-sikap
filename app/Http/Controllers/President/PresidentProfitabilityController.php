<?php

namespace App\Http\Controllers\President;

use App\Http\Controllers\Concerns\RecordsAuditTrail;
use App\Http\Controllers\Controller;
use App\Http\Requests\PigRegistry\FinalizeCycleProfitabilityRequest;
use App\Models\PigCycle;
use App\Models\ProfitabilitySnapshot;
use App\Services\PigRegistry\BreakEvenAnalysisService;
use App\Services\PigRegistry\ComputeCycleProfitabilityService;
use App\Services\PigRegistry\ProfitabilitySnapshotService;
use App\Services\PigRegistry\ProfitabilityValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PresidentProfitabilityController extends Controller
{
    use RecordsAuditTrail;

    public function __construct(
        private readonly ComputeCycleProfitabilityService $computeService,
        private readonly BreakEvenAnalysisService $breakEvenService,
        private readonly ProfitabilityValidationService $validationService,
        private readonly ProfitabilitySnapshotService $snapshotService,
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

        $snapshot = $cycle->profitabilitySnapshot;
        $profitability = $this->profitabilityFor($cycle);
        $advisory = $this->breakEvenService->analyze($cycle, $profitability);
        $validation = $this->validationService->validate($cycle, $profitability, $snapshot);
        $dataChanged = false;

        if ($snapshot !== null) {
            $dataChanged = $this->snapshotService->detectDataChanges($cycle, $snapshot);
        }

        $user = request()->user();
        $canFinalize = $dataChanged
            ? $user?->hasRole('president') && $cycle->isArchived()
            : $user?->hasRole('president') && $cycle->isArchived() && $snapshot === null;

        return view('profitability.show', [
            'cycle' => $cycle,
            'profitability' => $profitability,
            'snapshot' => $snapshot,
            'advisory' => $advisory,
            'validation' => $validation,
            'dataChanged' => $dataChanged,
            'canFinalize' => $canFinalize,
            'isPresident' => $user?->hasRole('president') ?? false,
        ]);
    }

    public function sharing(PigCycle $cycle): View
    {
        $cycle->load(['caretaker:id,name', 'profitabilitySnapshot.finalizedBy:id,name']);

        $snapshot = $cycle->profitabilitySnapshot;
        $profitability = $this->profitabilityFor($cycle);
        $dataChanged = false;

        if ($snapshot !== null) {
            $dataChanged = $this->snapshotService->detectDataChanges($cycle, $snapshot);
        }

        $user = request()->user();
        $canFinalize = $dataChanged
            ? $user?->hasRole('president') && $cycle->isArchived()
            : $user?->hasRole('president') && $cycle->isArchived() && $snapshot === null;

        $history = $snapshot !== null
            ? ProfitabilitySnapshot::query()
                ->where('pig_cycle_id', $cycle->id)
                ->with('finalizedBy:id,name')
                ->orderByDesc('version_number')
                ->get()
            : collect();

        return view('profitability.sharing', [
            'cycle' => $cycle,
            'profitability' => $profitability,
            'snapshot' => $snapshot,
            'canFinalize' => $canFinalize,
            'dataChanged' => $dataChanged,
            'isPresident' => $user?->hasRole('president') ?? false,
            'snapshotHistory' => $history,
        ]);
    }

    public function finalize(
        FinalizeCycleProfitabilityRequest $request,
        PigCycle $cycle,
    ): RedirectResponse {
        $validated = $request->validated();

        $isReFinalize = ! empty($validated['re_finalize']);
        $force = $isReFinalize;

        $snapshot = $this->snapshotService->finalize(
            $cycle,
            $request->user(),
            $validated['notes'] ?? null,
            $force,
            $validated['re_finalize_reason_code'] ?? null,
            $validated['re_finalize_reason_notes'] ?? null,
        );

        $action = $isReFinalize ? 're-finalized' : 'finalized';
        $this->recordAudit(
            $request,
            "profitability_{$action}",
            ucfirst($action)." profitability snapshot v{$snapshot->version_number} for cycle {$cycle->batch_code}.",
            'profitability',
            [
                'cycle_id' => $cycle->id,
                'snapshot_id' => $snapshot->id,
                'version_number' => $snapshot->version_number,
            ]
        );

        $message = $isReFinalize
            ? "Profitability snapshot was re-finalized as version {$snapshot->version_number}."
            : 'Profitability snapshot was finalized for reports and resolutions.';

        return redirect()
            ->route('profitability.sharing', $cycle)
            ->with('status', $message);
    }

    /**
     * @return array<string, mixed>
     */
    private function profitabilityFor(PigCycle $cycle): array
    {
        if ($cycle->relationLoaded('profitabilitySnapshot') && $cycle->profitabilitySnapshot !== null) {
            return $cycle->profitabilitySnapshot->toProfitabilitySummary();
        }

        return $this->computeService->compute($cycle);
    }
}