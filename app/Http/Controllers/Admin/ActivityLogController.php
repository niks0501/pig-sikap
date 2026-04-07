<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    /**
     * Display activity logs page or filtered logs data.
     */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json($this->paginatedLogs($request));
        }

        return view('admin.activity-logs.index');
    }

    /**
     * Build activity logs query from filters.
     */
    private function logsQuery(Request $request): Builder
    {
        $search = trim((string) $request->query('search', ''));
        $module = trim((string) $request->query('module', ''));
        $action = trim((string) $request->query('action', ''));

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
            ->when($module !== '', fn (Builder $query) => $query->where('module', $module))
            ->when($action !== '', fn (Builder $query) => $query->where('action', $action))
            ->latest('created_at');
    }

    /**
     * Return paginated logs payload.
     *
     * @return array<string, mixed>
     */
    private function paginatedLogs(Request $request): array
    {
        $perPage = max(5, min((int) $request->query('per_page', 10), 50));

        $paginator = $this->logsQuery($request)->paginate($perPage);

        $items = $paginator->getCollection()->map(fn (AuditTrail $log) => [
            'id' => $log->id,
            'user' => $log->user?->name ?? 'System',
            'email' => $log->user?->email,
            'action' => $log->action,
            'module' => $log->module,
            'description' => $log->description,
            'ip_address' => $log->ip_address,
            'created_at' => optional($log->created_at)->toDateTimeString(),
        ]);

        return [
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'modules' => AuditTrail::query()->select('module')->distinct()->orderBy('module')->pluck('module')->values(),
                'actions' => AuditTrail::query()->select('action')->distinct()->orderBy('action')->pluck('action')->values(),
            ],
        ];
    }
}
