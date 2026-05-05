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
        $cycle->load(['caretaker:id,name', 'profitabilitySnapshot.finalizedBy:id,name', 'profitabilitySnapshot.memberShareDistributions.member:id,name']);

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

        // Load active members for per-member distribution
        $members = \App\Models\User::query()
            ->where('is_active', true)
            ->whereHas('role', fn ($q) => $q->whereIn('slug', ['member', 'officer', 'president', 'treasurer', 'secretary']))
            ->whereDoesntHave('role', fn ($q) => $q->where('slug', 'system_admin'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Load existing member distributions if snapshot exists
        $existingDistributions = [];
        if ($snapshot !== null) {
            $snapshot->load('memberShareDistributions.member:id,name');
            $existingDistributions = $snapshot->memberShareDistributions
                ->map(fn ($d) => [
                    'user_id' => $d->user_id,
                    'name' => $d->member?->name ?? 'Unknown',
                    'allocated_amount' => (float) $d->allocated_amount,
                    'notes' => $d->notes,
                ])
                ->values()
                ->all();
        }

        return view('profitability.sharing', [
            'cycle' => $cycle,
            'profitability' => $profitability,
            'snapshot' => $snapshot,
            'canFinalize' => $canFinalize,
            'dataChanged' => $dataChanged,
            'isPresident' => $user?->hasRole('president') ?? false,
            'snapshotHistory' => $history,
            'members' => $members,
            'existingDistributions' => $existingDistributions,
            'hasLoss' => (float) $profitability['net_profit_or_loss'] < 0,
            'hasReceivables' => (float) ($profitability['receivables'] ?? 0) > 0,
            'isCorrectionMode' => $cycle->correction_mode ?? false,
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
            $validated['loss_acknowledged'] ?? false,
            $validated['receivables_acknowledged'] ?? false,
            $validated['member_distributions'] ?? [],
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
     * Enable correction mode on a finalized cycle so expenses and sales can be edited.
     */
    public function enableCorrectionMode(Request $request, PigCycle $cycle): RedirectResponse
    {
        if (! $request->user()?->hasRole('president')) {
            abort(403, 'Only the president can enable correction mode.');
        }

        if (! $cycle->isArchived()) {
            return back()->withErrors(['cycle' => 'Correction mode can only be enabled on archived cycles.']);
        }

        if ($cycle->profitabilitySnapshot === null) {
            return back()->withErrors(['cycle' => 'This cycle has not been finalized yet. Finalize it first before enabling correction mode.']);
        }

        $cycle->update([
            'correction_mode' => true,
            'correction_mode_enabled_at' => now(),
            'correction_mode_enabled_by' => $request->user()->id,
        ]);

        $this->recordAudit(
            $request,
            'profitability_correction_mode_enabled',
            "Enabled correction mode for cycle {$cycle->batch_code}. Expense and sale records are now editable.",
            'profitability',
            ['cycle_id' => $cycle->id]
        );

        return redirect()
            ->route('profitability.sharing', $cycle)
            ->with('status', 'Correction mode enabled. You can now edit expenses and sales for this cycle. Re-finalize when corrections are complete.');
    }

    /**
     * Disable correction mode without re-finalizing (cancel corrections).
     */
    public function disableCorrectionMode(Request $request, PigCycle $cycle): RedirectResponse
    {
        if (! $request->user()?->hasRole('president')) {
            abort(403, 'Only the president can disable correction mode.');
        }

        $cycle->update([
            'correction_mode' => false,
            'correction_mode_enabled_at' => null,
            'correction_mode_enabled_by' => null,
        ]);

        $this->recordAudit(
            $request,
            'profitability_correction_mode_disabled',
            "Disabled correction mode for cycle {$cycle->batch_code} without re-finalizing.",
            'profitability',
            ['cycle_id' => $cycle->id]
        );

        return redirect()
            ->route('profitability.sharing', $cycle)
            ->with('status', 'Correction mode disabled. No corrections were made.');
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