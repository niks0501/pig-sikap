<?php

namespace App\Services\PigRegistry;

use App\Models\PigCycle;
use App\Models\PigCycleStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePigCycleService
{
    public function __construct(
        private readonly GeneratePigProfilesService $generatePigProfilesService
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, User $actor): PigCycle
    {
        return DB::transaction(function () use ($payload, $actor): PigCycle {
            $initialCount = (int) ($payload['initial_count'] ?? 0);
            $hasPigProfiles = (bool) ($payload['has_pig_profiles'] ?? false);

            if ($initialCount < 1) {
                throw ValidationException::withMessages([
                    'initial_count' => 'Initial count must be at least 1.',
                ]);
            }

            $cycle = PigCycle::create([
                'batch_code' => (string) $payload['batch_code'],
                'caretaker_user_id' => $payload['caretaker_user_id'] ?? null,
                'cycle_number' => $payload['cycle_number'] ?? null,
                'date_of_purchase' => $payload['date_of_purchase'],
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

            PigCycleStatusHistory::create([
                'batch_id' => $cycle->id,
                'old_stage' => null,
                'new_stage' => $cycle->stage,
                'old_status' => null,
                'new_status' => $cycle->status,
                'remarks' => 'Initial cycle registration.',
                'changed_by' => $actor->id,
            ]);

            if ($hasPigProfiles) {
                $this->generatePigProfilesService->handle($cycle, $initialCount, $actor->id);
            }

            return $cycle;
        });
    }
}
