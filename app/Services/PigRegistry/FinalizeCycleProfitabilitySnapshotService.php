<?php

namespace App\Services\PigRegistry;

use App\Models\PigCycle;
use App\Models\ProfitabilitySnapshot;
use App\Models\User;

/**
 * Backward-compatible wrapper. Delegates to ProfitabilitySnapshotService.
 */
class FinalizeCycleProfitabilitySnapshotService
{
    public function __construct(
        private readonly ProfitabilitySnapshotService $snapshotService
    ) {}

    /**
     * @deprecated Use ProfitabilitySnapshotService::finalize() instead.
     */
    public function handle(PigCycle $cycle, User $actor, ?string $notes = null): ProfitabilitySnapshot
    {
        return $this->snapshotService->finalize($cycle, $actor, $notes);
    }
}