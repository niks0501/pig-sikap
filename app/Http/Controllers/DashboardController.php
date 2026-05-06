<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Route the user to their role-specific dashboard.
     */
    public function index(): View
    {
        $user = auth()->user();
        $roleSlug = $user->role?->slug;

        return match ($roleSlug) {
            'president', 'system_admin' => view('dashboard.president'),
            'secretary' => view('dashboard.secretary'),
            'treasurer' => view('dashboard.treasurer'),
            'canvasser' => view('dashboard.canvasser'),
            'caretaker' => view('dashboard.caretaker'),
            'member' => view('dashboard.member'),
            default => view('dashboard.member'),
        };
    }
}
