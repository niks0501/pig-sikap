<?php

namespace App\Events\Workflow;

use App\Models\Resolution;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a new resolution is created from a meeting.
 */
class ResolutionCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Resolution $resolution
    ) {}
}
