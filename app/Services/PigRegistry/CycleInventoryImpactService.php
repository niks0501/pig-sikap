<?php

namespace App\Services\PigRegistry;

use App\Models\PigCycle;
use App\Models\PigCycleAdjustment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CycleInventoryImpactService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function apply(PigCycle $cycle, array $payload, User $actor): PigCycleAdjustment
    {
        return DB::transaction(function () use ($cycle, $payload, $actor): PigCycleAdjustment {
            $sourceEventKey = $this->normalizeString($payload['source_event_key'] ?? null);

            if ($sourceEventKey !== null) {
                $existing = PigCycleAdjustment::query()
                    ->where('source_event_key', $sourceEventKey)
                    ->first();

                if ($existing !== null) {
                    if ((int) $existing->batch_id !== (int) $cycle->id) {
                        throw ValidationException::withMessages([
                            'source_event_key' => 'Source event key is already associated with a different cycle.',
                        ]);
                    }

                    return $existing;
                }
            }

            $lockedCycle = PigCycle::query()
                ->whereKey($cycle->id)
                ->lockForUpdate()
                ->firstOrFail();

            $before = (int) $lockedCycle->current_count;
            $adjustmentType = $this->resolveAdjustmentType($payload);
            $delta = $this->resolveDelta($before, $adjustmentType, $payload);

            if ($delta === 0) {
                throw ValidationException::withMessages([
                    'quantity_change' => 'Adjustment must change the current count.',
                ]);
            }

            $after = $before + $delta;

            if ($after < 0) {
                throw ValidationException::withMessages([
                    'quantity_change' => 'Adjustment cannot result in a negative count.',
                ]);
            }

            $lockedCycle->update([
                'current_count' => $after,
                'last_reviewed_at' => now(),
            ]);

            return PigCycleAdjustment::query()->create([
                'batch_id' => $lockedCycle->id,
                'adjustment_type' => $adjustmentType,
                'quantity_before' => $before,
                'quantity_change' => $delta,
                'quantity_after' => $after,
                'reason' => (string) ($payload['reason'] ?? 'data correction'),
                'remarks' => $payload['remarks'] ?? null,
                'source_module' => $this->normalizeString($payload['source_module'] ?? 'pig_registry'),
                'source_type' => $this->normalizeString($payload['source_type'] ?? null),
                'source_id' => $payload['source_id'] ?? null,
                'source_event_key' => $sourceEventKey,
                'created_by' => $actor->id,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function applyDelta(PigCycle $cycle, int $delta, string $reason, User $actor, array $context = []): ?PigCycleAdjustment
    {
        if ($delta === 0) {
            return null;
        }

        return $this->apply($cycle, [
            'adjustment_type' => $delta > 0 ? 'increase' : 'decrease',
            'quantity_change' => abs($delta),
            'reason' => $reason,
            ...$context,
        ], $actor);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveAdjustmentType(array $payload): string
    {
        $adjustmentType = (string) ($payload['adjustment_type'] ?? 'correction');

        if (in_array($adjustmentType, PigCycleAdjustment::ADJUSTMENT_TYPES, true)) {
            return $adjustmentType;
        }

        return 'correction';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveDelta(int $before, string $adjustmentType, array $payload): int
    {
        $requestedChange = (int) ($payload['quantity_change'] ?? 0);

        return match ($adjustmentType) {
            'increase' => abs($requestedChange),
            'decrease' => -abs($requestedChange),
            default => array_key_exists('quantity_after', $payload) && $payload['quantity_after'] !== null
                ? ((int) $payload['quantity_after']) - $before
                : $requestedChange,
        };
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
