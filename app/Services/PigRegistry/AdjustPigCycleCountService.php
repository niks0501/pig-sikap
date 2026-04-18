<?php

namespace App\Services\PigRegistry;

use App\Models\PigCycle;
use App\Models\User;
use App\Models\PigCycleAdjustment;

class AdjustPigCycleCountService
{
    public function __construct(
        private readonly CycleInventoryImpactService $cycleInventoryImpactService
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(PigCycle $cycle, array $payload, User $actor): PigCycleAdjustment
    {
        return $this->cycleInventoryImpactService->apply($cycle, $payload, $actor);
    }
}
