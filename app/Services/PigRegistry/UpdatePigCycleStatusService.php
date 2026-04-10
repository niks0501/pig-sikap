<?php

namespace App\Services\PigRegistry;

use App\Models\PigCycle;
use App\Models\PigCycleStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdatePigCycleStatusService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(PigCycle $cycle, array $payload, User $actor): PigCycleStatusHistory
    {
        return DB::transaction(function () use ($cycle, $payload, $actor): PigCycleStatusHistory {
            $lockedCycle = PigCycle::query()
                ->whereKey($cycle->id)
                ->lockForUpdate()
                ->firstOrFail();

            $newStage = (string) ($payload['new_stage'] ?? $lockedCycle->stage);
            $newStatus = (string) ($payload['new_status'] ?? $lockedCycle->status);

            if ($newStage === $lockedCycle->stage && $newStatus === $lockedCycle->status) {
                throw ValidationException::withMessages([
                    'new_status' => 'Select a new stage or status to update this cycle.',
                ]);
            }

            $history = PigCycleStatusHistory::create([
                'batch_id' => $lockedCycle->id,
                'old_stage' => $lockedCycle->stage,
                'new_stage' => $newStage,
                'old_status' => $lockedCycle->status,
                'new_status' => $newStatus,
                'remarks' => $payload['remarks'] ?? null,
                'changed_by' => $actor->id,
            ]);

            $lockedCycle->update([
                'stage' => $newStage,
                'status' => $newStatus,
                'last_reviewed_at' => now(),
            ]);

            return $history;
        });
    }
}
