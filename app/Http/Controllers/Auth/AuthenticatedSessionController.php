<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AuditTrail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user();

        if (! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Your account is inactive. Please contact the system administrator.',
            ]);
        }

        $user->update([
            'last_login_at' => now(),
        ]);

        AuditTrail::create([
            'user_id' => $user->id,
            'action' => 'login',
            'module' => 'authentication',
            'description' => 'User logged into the system.',
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        $request->session()->regenerate();

        if ($user->must_change_password && $this->intendedUrlIsEmailVerification($request)) {
            return redirect()->intended(route('password.force.edit', absolute: false));
        }

        if ($user->must_change_password) {
            return redirect()->route('password.force.edit');
        }

        if ($user->isSystemAdmin()) {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Let first-login users complete a verification link they clicked before login.
     */
    private function intendedUrlIsEmailVerification(Request $request): bool
    {
        $intendedUrl = $request->session()->get('url.intended');

        if (! is_string($intendedUrl)) {
            return false;
        }

        $path = parse_url($intendedUrl, PHP_URL_PATH);

        return is_string($path) && str_starts_with(trim($path, '/'), 'verify-email/');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if ($request->user()) {
            AuditTrail::create([
                'user_id' => $request->user()->id,
                'action' => 'logout',
                'module' => 'authentication',
                'description' => 'User logged out of the system.',
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
