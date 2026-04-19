<?php

namespace App\Services\PigRegistry;

use App\Models\PigCycle;
use App\Models\PigCycleStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdatePigCycleStatusService
{
    public function __construct(
        private readonly CycleStatusTransitionPolicy $cycleStatusTransitionPolicy
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $options
     */
    public function handle(PigCycle $cycle, array $payload, User $actor, array $options = []): PigCycleStatusHistory
    {
        return DB::transaction(function () use ($cycle, $payload, $actor, $options): PigCycleStatusHistory {
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

            $this->cycleStatusTransitionPolicy->assertAllowed(
                $lockedCycle,
                $newStage,
                $newStatus
            );

            $wasArchived = $lockedCycle->isArchived();
            $willBeArchived = in_array($newStatus, PigCycle::ARCHIVED_STATUSES, true) || $newStage === 'Completed';

            $transitionType = $this->resolveTransitionType($options, $wasArchived, $willBeArchived);
            $transitionOrigin = $this->normalizeString($options['transition_origin'] ?? null) ?? 'cycle_status_update';
            $transitionKey = $this->normalizeString($options['transition_key'] ?? null);
            $context = $options['context'] ?? null;

            $history = PigCycleStatusHistory::create([
                'batch_id' => $lockedCycle->id,
                'old_stage' => $lockedCycle->stage,
                'new_stage' => $newStage,
                'old_status' => $lockedCycle->status,
                'new_status' => $newStatus,
                'remarks' => $payload['remarks'] ?? null,
                'transition_type' => $transitionType,
                'transition_origin' => $transitionOrigin,
                'transition_key' => $transitionKey,
                'context_json' => is_array($context) ? $context : null,
                'changed_by' => $actor->id,
            ]);

            $updates = [
                'stage' => $newStage,
                'status' => $newStatus,
                'last_reviewed_at' => now(),
            ];

            if (! $wasArchived && $willBeArchived) {
                $updates['archived_at'] = now();
                $updates['archived_by'] = $actor->id;
            }

            $lockedCycle->update($updates);

            return $history;
        });
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function resolveTransitionType(array $options, bool $wasArchived, bool $willBeArchived): string
    {
        $explicitType = $this->normalizeString($options['transition_type'] ?? null);

        if ($explicitType !== null) {
            return $explicitType;
        }

        if (! $wasArchived && $willBeArchived) {
            return 'archive';
        }

        return 'status_update';
    }

    private function normalizeString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
