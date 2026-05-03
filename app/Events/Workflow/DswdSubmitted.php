<?php

namespace App\Events\Workflow;

use App\Models\DswdSubmission;
use App\Models\Resolution;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a DSWD submission status is updated.
 */
class DswdSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Resolution $resolution,
        public readonly DswdSubmission $submission
    ) {}
}
