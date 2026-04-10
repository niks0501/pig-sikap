<?php

namespace App\Services\PigRegistry;

use App\Models\PigCycle;
use App\Models\PigCycleAdjustment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdjustPigCycleCountService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(PigCycle $cycle, array $payload, User $actor): PigCycleAdjustment
    {
        return DB::transaction(function () use ($cycle, $payload, $actor): PigCycleAdjustment {
            $lockedCycle = PigCycle::query()
                ->whereKey($cycle->id)
                ->lockForUpdate()
                ->firstOrFail();

            $before = (int) $lockedCycle->current_count;
            $requestedChange = (int) ($payload['quantity_change'] ?? 0);
            $adjustmentType = (string) $payload['adjustment_type'];

            $delta = match ($adjustmentType) {
                'increase' => abs($requestedChange),
                'decrease' => -abs($requestedChange),
                default => array_key_exists('quantity_after', $payload) && $payload['quantity_after'] !== null
                    ? ((int) $payload['quantity_after']) - $before
                    : $requestedChange,
            };

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

            return PigCycleAdjustment::create([
                'batch_id' => $lockedCycle->id,
                'adjustment_type' => $adjustmentType,
                'quantity_before' => $before,
                'quantity_change' => $delta,
                'quantity_after' => $after,
                'reason' => (string) $payload['reason'],
                'remarks' => $payload['remarks'] ?? null,
                'created_by' => $actor->id,
            ]);
        });
    }
}
