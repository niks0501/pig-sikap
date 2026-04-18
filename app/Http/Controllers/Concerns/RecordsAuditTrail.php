<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AuditTrail;
use Illuminate\Http\Request;

trait RecordsAuditTrail
{
    protected function recordAudit(
        Request $request,
        string $action,
        string $description,
        string $module = 'pig_registry',
        ?array $context = null
    ): void {
        AuditTrail::create([
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
