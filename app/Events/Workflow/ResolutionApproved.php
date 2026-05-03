<?php

namespace App\Events\Workflow;

use App\Models\Resolution;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a resolution reaches the 75% approval threshold.
 */
class ResolutionApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Resolution $resolution,
        public readonly float $approvalPercentage
    ) {}
}
