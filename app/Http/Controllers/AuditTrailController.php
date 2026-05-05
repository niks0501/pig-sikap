<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\PigCycle;
use App\Models\Resolution;
use App\Models\Meeting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditTrailController extends Controller
{
    /**
     * Display the audit trail logbook page (president only).
     */
    public function index(): View
    {
        return view('audit-trails.index');
    }

    /**
     * Return paginated audit trail data for the Vue component.
     * Simpler than the admin version: search filter only, no module/action dropdowns.
     */
    public function json(Request $request): JsonResponse
    {
        $perPage = max(5, min((int) $request->query('per_page', 15), 50));

        $paginator = $this->logsQuery($request)->paginate($perPage);

        $items = $paginator->getCollection()->map(fn (AuditTrail $log) => [
            'id' => $log->id,
            'user' => $log->user?->name ?? 'System',
            'email' => $log->user?->email,
            'action' => $log->action,
            'module' => $log->module,
            'description' => $log->description,
            'reference' => $this->buildReference($log),
            'context_json' => is_array($log->context_json) ? $log->context_json : [],
            'ip_address' => $log->ip_address,
            'created_at' => optional($log->created_at)->toDateTimeString(),
        ]);

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Export filtered audit trail entries as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $logs = $this->logsQuery($request)->limit(5000)->get();

        $filename = 'audit-trail-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($logs): void {
            $handle = fopen('php://output', 'w');

            // CSV headers
            fputcsv($handle, ['Date/Time', 'User', 'Email', 'Action', 'Module', 'Description', 'Reference', 'IP Address']);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    optional($log->created_at)->toDateTimeString(),
                    $log->user?->name ?? 'System',
                    $log->user?->email ?? '',
                    $log->action,
                    $log->module,
                    $log->description,
                    $this->buildReference($log),
                    $log->ip_address ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Return paginated audit trail data scoped to a specific pig cycle.
     */
    public function cycleAudit(Request $request, PigCycle $cycle): JsonResponse
    {
        $perPage = max(5, min((int) $request->query('per_page', 10), 25));

        $paginator = AuditTrail::query()
            ->with('user:id,name,email')
            ->where(function (Builder $query) use ($cycle): void {
                $query->where('context_json->cycle_id', $cycle->id)
                    ->orWhere('context_json->cycle_id', (string) $cycle->id);
            })
            ->latest('created_at')
            ->paginate($perPage);

        $items = $paginator->getCollection()->map(fn (AuditTrail $log) => [
            'id' => $log->id,
            'user' => $log->user?->name ?? 'System',
            'action' => $log->action,
            'module' => $log->module,
            'description' => $log->description,
            'created_at' => optional($log->created_at)->toDateTimeString(),
        ]);

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Return paginated audit trail data scoped to a specific resolution.
     */
    public function resolutionAudit(Request $request, Resolution $resolution): JsonResponse
    {
        $perPage = max(5, min((int) $request->query('per_page', 10), 25));

        $paginator = AuditTrail::query()
            ->with('user:id,name,email')
            ->where(function (Builder $query) use ($resolution): void {
                $query->where('context_json->resolution_id', $resolution->id)
                    ->orWhere('context_json->resolution_id', (string) $resolution->id);
            })
            ->latest('created_at')
            ->paginate($perPage);

        $items = $paginator->getCollection()->map(fn (AuditTrail $log) => [
            'id' => $log->id,
            'user' => $log->user?->name ?? 'System',
            'action' => $log->action,
            'module' => $log->module,
            'description' => $log->description,
            'created_at' => optional($log->created_at)->toDateTimeString(),
        ]);

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Return paginated audit trail data scoped to a specific meeting.
     */
    public function meetingAudit(Request $request, Meeting $meeting): JsonResponse
    {
        $perPage = max(5, min((int) $request->query('per_page', 10), 25));

        $paginator = AuditTrail::query()
            ->with('user:id,name,email')
            ->where(function (Builder $query) use ($meeting): void {
                $query->where('context_json->meeting_id', $meeting->id)
                    ->orWhere('context_json->meeting_id', (string) $meeting->id);
            })
            ->latest('created_at')
            ->paginate($perPage);

        $items = $paginator->getCollection()->map(fn (AuditTrail $log) => [
            'id' => $log->id,
            'user' => $log->user?->name ?? 'System',
            'action' => $log->action,
            'module' => $log->module,
            'description' => $log->description,
            'created_at' => optional($log->created_at)->toDateTimeString(),
        ]);

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Build the search query for the main audit trail listing.
     */
    private function logsQuery(Request $request): Builder
    {
        $search = trim((string) $request->query('search', ''));

        return AuditTrail::query()
            ->with('user:id,name,email')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('description', 'like', "%{$search}%")
                        ->orWhere('module', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%")
                        ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('created_at');
    }

    /**
     * Build a human-readable reference string from context_json.
     */
    private function buildReference(AuditTrail $log): ?string
    {
        $context = is_array($log->context_json) ? $log->context_json : [];

        $parts = [];

        if (
            isset($context['cycle_batch_code'])
            && is_string($context['cycle_batch_code'])
            && $context['cycle_batch_code'] !== ''
        ) {
            $parts[] = "Cycle {$context['cycle_batch_code']}";
        } elseif (isset($context['cycle_id']) && is_numeric($context['cycle_id'])) {
            $parts[] = 'Cycle #'.(int) $context['cycle_id'];
        }

        if (isset($context['resolution_id']) && is_numeric($context['resolution_id'])) {
            $parts[] = 'Resolution #'.(int) $context['resolution_id'];
        }

        if (isset($context['meeting_id']) && is_numeric($context['meeting_id'])) {
            $parts[] = 'Meeting #'.(int) $context['meeting_id'];
        }

        if (isset($context['task_id']) && is_numeric($context['task_id'])) {
            $parts[] = 'Task #'.(int) $context['task_id'];
        }

        if (isset($context['incident_id']) && is_numeric($context['incident_id'])) {
            $parts[] = 'Incident #'.(int) $context['incident_id'];
        }

        if ($parts === []) {
            return null;
        }

        return implode(' • ', $parts);
    }
}
