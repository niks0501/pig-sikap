<?php

namespace App\Services;

use App\Models\AuditTrail;
use Illuminate\Http\Request;

/**
 * Centralized service for recording audit trail entries.
 * Used by the RecordsAuditTrail trait and by any code that needs
 * to log system activity for transparency and accountability.
 */
class AuditTrailService
{
    /**
     * Record an audit trail entry.
     *
     * @param  array<string, mixed>|null  $context  Structured metadata about the affected record
     */
    public function record(
        Request $request,
        string $action,
        string $description,
        string $module = 'pig_registry',
        ?array $context = null
    ): AuditTrail {
        return AuditTrail::create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'context_json' => $context,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);
    }
}
