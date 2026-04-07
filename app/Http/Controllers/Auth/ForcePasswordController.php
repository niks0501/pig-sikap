<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ForcePasswordController extends Controller
{
    /**
     * Show forced password change page.
     */
    public function edit(Request $request): View
    {
        return view('auth.change-password', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update password and clear force-change flag.
     */
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => [
                        'current_password' => ['The provided current password is incorrect.'],
                    ],
                ], 422);
            }

            return back()->withErrors([
                'current_password' => 'The provided current password is incorrect.',
            ])->withInput();
        }

        $user->update([
            'password' => $validated['password'],
            'must_change_password' => false,
        ]);

        AuditTrail::create([
            'user_id' => $user->id,
            'action' => 'password_changed',
            'module' => 'authentication',
            'description' => 'User completed the mandatory first-login password change.',
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        $redirect = $user->isSystemAdmin()
            ? route('admin.dashboard')
            : route('dashboard');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Password updated successfully.',
                'redirect' => $redirect,
            ]);
        }

        return redirect($redirect)->with('status', 'password-updated');
    }
}
