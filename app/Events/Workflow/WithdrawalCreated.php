<?php

namespace App\Events\Workflow;

use App\Models\Resolution;
use App\Models\Withdrawal;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a withdrawal is created against a resolution.
 */
class WithdrawalCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Withdrawal $withdrawal,
        public readonly Resolution $resolution
    ) {}
}
