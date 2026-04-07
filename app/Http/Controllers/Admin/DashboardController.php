<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use App\Models\Role;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the system admin dashboard.
     */
    public function index(): View
    {
        $summary = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'inactive_users' => User::where('is_active', false)->count(),
            'roles_count' => Role::count(),
        ];

        $recentLogins = User::query()
            ->with('role:id,name,slug')
            ->whereNotNull('last_login_at')
            ->latest('last_login_at')
            ->limit(6)
            ->get(['id', 'name', 'email', 'role_id', 'last_login_at'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->name,
                'last_login_at' => optional($user->last_login_at)->toDateTimeString(),
            ]);

        $recentActivityLogs = AuditTrail::query()
            ->with('user:id,name,email,role_id')
            ->latest()
            ->limit(10)
            ->get(['id', 'user_id', 'action', 'module', 'description', 'created_at'])
            ->map(fn (AuditTrail $log) => [
                'id' => $log->id,
                'user' => $log->user?->name ?? 'System',
                'action' => $log->action,
                'module' => $log->module,
                'description' => $log->description,
                'created_at' => optional($log->created_at)->toDateTimeString(),
            ]);

        return view('admin.dashboard', [
            'summary' => $summary,
            'recentLogins' => $recentLogins,
            'recentActivityLogs' => $recentActivityLogs,
        ]);
    }
}
