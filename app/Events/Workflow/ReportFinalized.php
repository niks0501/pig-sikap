<?php

namespace App\Events\Workflow;

use App\Models\LiquidationReport;
use App\Models\Withdrawal;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a liquidation report is finalized.
 */
class ReportFinalized
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly LiquidationReport $report,
        public readonly Withdrawal $withdrawal
    ) {}
}
