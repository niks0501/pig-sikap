<?php

namespace App\Services\PigRegistry;

use App\Models\PigBatch;
use App\Models\PigBatchStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdatePigBatchStatusService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(PigBatch $batch, array $payload, User $actor): PigBatchStatusHistory
    {
        return DB::transaction(function () use ($batch, $payload, $actor): PigBatchStatusHistory {
            $lockedBatch = PigBatch::query()
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            $newStage = (string) ($payload['new_stage'] ?? $lockedBatch->stage);
            $newStatus = (string) ($payload['new_status'] ?? $lockedBatch->status);

            if ($newStage === $lockedBatch->stage && $newStatus === $lockedBatch->status) {
                throw ValidationException::withMessages([
                    'new_status' => 'Select a new stage or status to update this batch.',
                ]);
            }

            $history = PigBatchStatusHistory::create([
                'batch_id' => $lockedBatch->id,
                'old_stage' => $lockedBatch->stage,
                'new_stage' => $newStage,
                'old_status' => $lockedBatch->status,
                'new_status' => $newStatus,
                'remarks' => $payload['remarks'] ?? null,
                'changed_by' => $actor->id,
            ]);

            $lockedBatch->update([
                'stage' => $newStage,
                'status' => $newStatus,
                'last_reviewed_at' => now(),
            ]);

            return $history;
        });
    }
}
