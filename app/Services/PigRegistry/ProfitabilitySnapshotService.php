<?php

namespace App\Services\PigRegistry;

use App\Models\PigCycle;
use App\Models\ProfitabilitySnapshot;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProfitabilitySnapshotService
{
    public function __construct(
        private readonly ComputeCycleProfitabilityService $computeService,
        private readonly ProfitabilityValidationService $validationService,
    ) {}

    public function finalize(
        PigCycle $cycle,
        User $actor,
        ?string $notes = null,
        bool $force = false,
        ?string $reasonCode = null,
        ?string $reasonNotes = null,
    ): ProfitabilitySnapshot {
        return DB::transaction(function () use ($cycle, $actor, $notes, $force, $reasonCode, $reasonNotes): ProfitabilitySnapshot {
            $lockedCycle = PigCycle::query()
                ->whereKey($cycle->id)
                ->lockForUpdate()
                ->firstOrFail();

            $computed = $this->computeService->compute($lockedCycle);
            $latestSnapshot = $this->latestCurrentSnapshot($lockedCycle);

            $validation = $this->validationService->validate($lockedCycle, $computed, $latestSnapshot);

            if (! $validation['can_finalize']) {
                throw ValidationException::withMessages([
                    'cycle' => $validation['blocking_errors'],
                ]);
            }

            $currentHash = $this->computeService->computeSourceHash($lockedCycle, $computed['computation_version']);

            $isReFinalize = $latestSnapshot !== null;

            if ($isReFinalize && $latestSnapshot->source_hash === $currentHash && ! $force) {
                return $latestSnapshot;
            }

            if ($isReFinalize && ! $force) {
                throw ValidationException::withMessages([
                    'cycle' => ['Data has changed after the last finalization. Use re-finalize to create a new version with a reason.'],
                ]);
            }

            if ($isReFinalize) {
                if ($reasonCode === null || trim($reasonCode) === '') {
                    throw ValidationException::withMessages([
                        're_finalize_reason_code' => ['A reason is required when re-finalizing a profitability snapshot.'],
                    ]);
                }

                if ($reasonNotes === null || mb_strlen(trim($reasonNotes)) < 10) {
                    throw ValidationException::withMessages([
                        're_finalize_reason_notes' => ['Please provide a reason note of at least 10 characters.'],
                    ]);
                }

                $lockedCycle->profitabilitySnapshot()
                    ->where('is_current', true)
                    ->update(['is_current' => false]);
            }

            $nextVersion = $isReFinalize
                ? ($latestSnapshot->version_number + 1)
                : 1;

            $nextSnapshotNumber = ProfitabilitySnapshot::max('snapshot_number') + 1;

            $snapshot = ProfitabilitySnapshot::query()->create([
                'pig_cycle_id' => $lockedCycle->id,
                'snapshot_number' => $nextSnapshotNumber,
                'version_number' => $nextVersion,
                'gross_income' => $computed['gross_income'],
                'total_collected' => $computed['total_collected'],
                'receivables' => $computed['receivables'],
                'total_expenses' => $computed['total_expenses'],
                'net_profit_or_loss' => $computed['net_profit_or_loss'],
                'distributable_profit' => $computed['distributable_profit'],
                'caretaker_share' => $computed['caretaker_share'],
                'member_share' => $computed['member_share'],
                'association_share' => $computed['association_share'],
                'expense_breakdown_json' => $computed['expense_breakdown'],
                'share_rule_json' => $computed['share_rule'],
                'sales_summary_json' => $computed['sales_summary'],
                'finalized_at' => now(),
                'finalized_by_user_id' => $actor->id,
                'notes' => $this->normalizeNotes($notes),
                'computation_version' => $computed['computation_version'],
                'source_hash' => $currentHash,
                'is_current' => true,
                'supersedes_snapshot_id' => $isReFinalize ? $latestSnapshot->id : null,
                're_finalize_reason_code' => $isReFinalize ? $reasonCode : null,
                're_finalize_reason_notes' => $isReFinalize ? $this->normalizeNotes($reasonNotes) : null,
            ]);

            return $snapshot;
        });
    }

    public function latestCurrentSnapshot(PigCycle $cycle): ?ProfitabilitySnapshot
    {
        return ProfitabilitySnapshot::query()
            ->where('pig_cycle_id', $cycle->id)
            ->where('is_current', true)
            ->latest('version_number')
            ->first();
    }

    /**
     * @return list<ProfitabilitySnapshot>
     */
    public function snapshotHistory(PigCycle $cycle): array
    {
        return ProfitabilitySnapshot::query()
            ->where('pig_cycle_id', $cycle->id)
            ->with(['finalizedBy:id,name'])
            ->orderByDesc('version_number')
            ->get()
            ->all();
    }

    public function detectDataChanges(PigCycle $cycle, ProfitabilitySnapshot $snapshot): bool
    {
        $currentHash = $this->computeService->computeSourceHash($cycle, $snapshot->computation_version);

        return $currentHash !== $snapshot->source_hash;
    }

    private function normalizeNotes(?string $notes): ?string
    {
        $notes = trim((string) $notes);

        return $notes === '' ? null : $notes;
    }
}