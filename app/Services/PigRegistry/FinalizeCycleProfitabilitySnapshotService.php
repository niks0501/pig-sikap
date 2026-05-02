<?php

namespace App\Services\PigRegistry;

use App\Models\PigCycle;
use App\Models\ProfitabilitySnapshot;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinalizeCycleProfitabilitySnapshotService
{
    public function __construct(
        private readonly ComputeCycleProfitabilityService $computeCycleProfitabilityService
    ) {}

    public function handle(PigCycle $cycle, User $actor, ?string $notes = null): ProfitabilitySnapshot
    {
        return DB::transaction(function () use ($cycle, $actor, $notes): ProfitabilitySnapshot {
            $lockedCycle = PigCycle::query()
                ->whereKey($cycle->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedCycle->isArchived()) {
                throw ValidationException::withMessages([
                    'cycle' => 'Finalize profitability only after the cycle is completed, sold, or closed.',
                ]);
            }

            $existingSnapshot = ProfitabilitySnapshot::query()
                ->where('pig_cycle_id', $lockedCycle->id)
                ->lockForUpdate()
                ->first();

            if ($existingSnapshot !== null) {
                return $existingSnapshot;
            }

            $profitability = $this->computeCycleProfitabilityService->handle($lockedCycle);

            return ProfitabilitySnapshot::query()->create([
                'pig_cycle_id' => $lockedCycle->id,
                'gross_income' => $profitability['gross_income'],
                'total_expenses' => $profitability['total_expenses'],
                'net_profit_or_loss' => $profitability['net_profit_or_loss'],
                'distributable_profit' => $profitability['distributable_profit'],
                'caretaker_share' => $profitability['caretaker_share'],
                'member_share' => $profitability['member_share'],
                'association_share' => $profitability['association_share'],
                'expense_breakdown_json' => $profitability['expense_breakdown'],
                'share_rule_json' => $profitability['share_rule'],
                'finalized_at' => now(),
                'finalized_by_user_id' => $actor->id,
                'notes' => $this->normalizeNotes($notes),
                'computation_version' => $profitability['computation_version'],
            ]);
        });
    }

    private function normalizeNotes(?string $notes): ?string
    {
        $notes = trim((string) $notes);

        return $notes === '' ? null : $notes;
    }
}
