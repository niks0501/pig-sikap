<?php

namespace App\Http\Controllers\Concerns;

use App\Services\AuditTrailService;
use Illuminate\Http\Request;

/**
 * Provides a convenience method for controllers to record audit trail entries.
 * Delegates to AuditTrailService for centralized recording logic.
 *
 * @mixin \App\Http\Controllers\Controller
 */
trait RecordsAuditTrail
{
    protected function recordAudit(
        Request $request,
        string $action,
        string $description,
        string $module = 'pig_registry',
        ?array $context = null
    ): void {
        app(AuditTrailService::class)->record(
            $request,
            $action,
            $description,
            $module,
            $context
        );
    }
}
