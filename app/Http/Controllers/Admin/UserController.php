<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display users page or JSON list.
     */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json($this->paginatedUsers($request));
        }

        return view('admin.users.index', [
            'roles' => Role::orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    /**
     * Store a new user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $temporaryPassword = null;

        if (empty($validated['password'])) {
            $temporaryPassword = Str::password(12);
            $validated['password'] = $temporaryPassword;
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role_id' => $validated['role_id'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'must_change_password' => true,
            'email_verified_at' => now(),
        ]);

        AuditTrail::create([
            'user_id' => $request->user()->id,
            'action' => 'user_created',
            'module' => 'user_management',
            'description' => "Created user {$user->email}.",
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        $user->load('role:id,name,slug');

        return response()->json([
            'message' => 'User created successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->name,
                'role_slug' => $user->role?->slug,
                'is_active' => $user->is_active,
                'must_change_password' => $user->must_change_password,
                'last_login_at' => optional($user->last_login_at)->toDateTimeString(),
                'created_at' => optional($user->created_at)->toDateTimeString(),
            ],
            'temporary_password' => $temporaryPassword,
        ], 201);
    }

    /**
     * Update user details.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $changes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
            'is_active' => $validated['is_active'],
        ];

        if (! empty($validated['password'])) {
            $changes['password'] = $validated['password'];
            $changes['must_change_password'] = true;
        }

        $originalRoleId = $user->role_id;

        $user->update($changes);
        $user->load('role:id,name,slug');

        $description = "Updated user {$user->email}.";

        if ($originalRoleId !== $user->role_id) {
            $description .= ' Role assignment was changed.';
        }

        AuditTrail::create([
            'user_id' => $request->user()->id,
            'action' => 'user_updated',
            'module' => 'user_management',
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        if ($originalRoleId !== $user->role_id) {
            AuditTrail::create([
                'user_id' => $request->user()->id,
                'action' => 'role_changed',
                'module' => 'user_management',
                'description' => "Changed role assignment for {$user->email}.",
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        }

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->name,
                'role_slug' => $user->role?->slug,
                'is_active' => $user->is_active,
                'must_change_password' => $user->must_change_password,
                'last_login_at' => optional($user->last_login_at)->toDateTimeString(),
                'created_at' => optional($user->created_at)->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Activate or deactivate a user account.
     */
    public function toggleStatus(Request $request, User $user): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'You cannot deactivate your own account.',
            ], 422);
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        AuditTrail::create([
            'user_id' => $request->user()->id,
            'action' => $user->is_active ? 'account_activated' : 'account_deactivated',
            'module' => 'user_management',
            'description' => "Changed account status for {$user->email}.",
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return response()->json([
            'message' => $user->is_active ? 'User activated successfully.' : 'User deactivated successfully.',
            'is_active' => $user->is_active,
        ]);
    }

    /**
     * Reset the selected user's password.
     */
    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $temporaryPassword = Str::password(12);

        $user->update([
            'password' => $temporaryPassword,
            'must_change_password' => true,
        ]);

        AuditTrail::create([
            'user_id' => $request->user()->id,
            'action' => 'password_reset',
            'module' => 'user_management',
            'description' => "Reset password for {$user->email}.",
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Password reset successfully.',
            'temporary_password' => $temporaryPassword,
        ]);
    }

    /**
     * Build users query based on filters.
     */
    private function usersQuery(Request $request): Builder
    {
        $search = trim((string) $request->query('search', ''));
        $role = trim((string) $request->query('role', ''));
        $status = trim((string) $request->query('status', 'all'));

        return User::query()
            ->with('role:id,name,slug')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($role !== '', function (Builder $query) use ($role): void {
                $query->whereHas('role', function (Builder $roleQuery) use ($role): void {
                    $roleQuery->where('slug', $role);
                });
            })
            ->when(in_array($status, ['active', 'inactive'], true), function (Builder $query) use ($status): void {
                $query->where('is_active', $status === 'active');
            })
            ->latest('created_at');
    }

    /**
     * Return paginated users payload.
     *
     * @return array<string, mixed>
     */
    private function paginatedUsers(Request $request): array
    {
        $perPage = max(5, min((int) $request->query('per_page', 10), 50));

        $paginator = $this->usersQuery($request)->paginate($perPage);

        $items = $paginator->getCollection()->map(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role?->name,
            'role_slug' => $user->role?->slug,
            'is_active' => $user->is_active,
            'must_change_password' => $user->must_change_password,
            'last_login_at' => optional($user->last_login_at)->toDateTimeString(),
            'created_at' => optional($user->created_at)->toDateTimeString(),
        ]);

        return [
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }
}
