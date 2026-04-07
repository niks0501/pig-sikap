<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    /**
     * Display roles page or role data.
     */
    public function index(Request $request): View|JsonResponse
    {
        $roles = Role::query()
            ->withCount('users')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description']);

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $roles,
            ]);
        }

        return view('admin.roles.index', [
            'roles' => $roles,
        ]);
    }
}
