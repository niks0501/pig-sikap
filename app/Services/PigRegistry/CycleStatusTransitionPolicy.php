<?php

namespace App\Services\PigRegistry;

use App\Models\PigCycle;
use Illuminate\Validation\ValidationException;

class CycleStatusTransitionPolicy
{
    /**
     * @var array<string, int>
     */
    private const STAGE_ORDER = [
        'Piglet' => 10,
        'Weaning' => 20,
        'Growing' => 30,
        'Fattening' => 40,
        'For Sale' => 50,
        'Completed' => 60,
    ];

    public function assertAllowed(PigCycle $cycle, string $newStage, string $newStatus): void
    {
        if (! in_array($newStage, PigCycle::STAGES, true)) {
            throw ValidationException::withMessages([
                'new_stage' => 'The selected stage is invalid.',
            ]);
        }

        if (! in_array($newStatus, PigCycle::STATUSES, true)) {
            throw ValidationException::withMessages([
                'new_status' => 'The selected status is invalid.',
            ]);
        }

        if ($cycle->isArchived()) {
            throw ValidationException::withMessages([
                'cycle' => 'Archived cycles are final and cannot be modified.',
            ]);
        }

        $oldStageOrder = self::STAGE_ORDER[$cycle->stage] ?? 0;
        $newStageOrder = self::STAGE_ORDER[$newStage] ?? 0;

        if ($newStageOrder < $oldStageOrder) {
            throw ValidationException::withMessages([
                'new_stage' => 'Cycle stage cannot move backward during regular status updates.',
            ]);
        }

        if ($newStatus === 'Closed' && $newStage !== 'Completed') {
            throw ValidationException::withMessages([
                'new_status' => 'Closed status requires the Completed stage.',
            ]);
        }

        if ($newStage === 'Completed' && ! in_array($newStatus, ['Sold', 'Closed'], true)) {
            throw ValidationException::withMessages([
                'new_stage' => 'Completed stage requires Sold or Closed status.',
            ]);
        }

        if ($newStatus === 'Sold' && ! in_array($newStage, ['For Sale', 'Completed'], true)) {
            throw ValidationException::withMessages([
                'new_status' => 'Sold status requires For Sale or Completed stage.',
            ]);
        }
    }
}
