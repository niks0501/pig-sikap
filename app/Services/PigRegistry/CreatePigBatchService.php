<?php

namespace App\Services\PigRegistry;

use App\Models\PigBatch;
use App\Models\PigBatchStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePigBatchService
{
    public function __construct(
        private readonly GeneratePigProfilesService $generatePigProfilesService
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, User $actor): PigBatch
    {
        return DB::transaction(function () use ($payload, $actor): PigBatch {
            $initialCount = (int) ($payload['initial_count'] ?? 0);
            $hasPigProfiles = (bool) ($payload['has_pig_profiles'] ?? false);

            if ($initialCount < 1) {
                throw ValidationException::withMessages([
                    'initial_count' => 'Initial count must be at least 1.',
                ]);
            }

            $batch = PigBatch::create([
                'batch_code' => (string) $payload['batch_code'],
                'breeder_id' => $payload['breeder_id'] ?? null,
                'caretaker_user_id' => $payload['caretaker_user_id'] ?? null,
                'cycle_number' => $payload['cycle_number'] ?? null,
                'birth_date' => $payload['birth_date'],
                'initial_count' => $initialCount,
                'current_count' => $initialCount,
                'average_weight' => $payload['average_weight'] ?? null,
                'stage' => (string) $payload['stage'],
                'status' => (string) $payload['status'],
                'has_pig_profiles' => $hasPigProfiles,
                'notes' => $payload['notes'] ?? null,
                'last_reviewed_at' => now(),
                'created_by' => $actor->id,
            ]);

            PigBatchStatusHistory::create([
                'batch_id' => $batch->id,
                'old_stage' => null,
                'new_stage' => $batch->stage,
                'old_status' => null,
                'new_status' => $batch->status,
                'remarks' => 'Initial batch registration.',
                'changed_by' => $actor->id,
            ]);

            if ($hasPigProfiles) {
                $this->generatePigProfilesService->handle($batch, $initialCount, $actor->id);
            }

            return $batch;
        });
    }
}
