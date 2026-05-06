<?php

namespace App\Providers;

use App\Models\User;
use App\Services\NavigationService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('generate-report', function (User $user, string $type): bool {
            if (in_array($user->role?->slug, ['system_admin', 'president'], true)) {
                return true;
            }

            if ($user->hasRole('treasurer') && in_array($type, ['expense', 'sales', 'monthly', 'quarterly', 'profitability', 'per-cycle', 'dswd-summary'], true)) {
                return true;
            }

            if ($user->hasRole('secretary') && in_array($type, ['inventory', 'health', 'mortality', 'per-cycle', 'dswd-summary'], true)) {
                return true;
            }

            if ($user->hasRole('canvasser') && in_array($type, ['per-cycle', 'dswd-summary'], true)) {
                return true;
            }

            if ($user->hasRole('caretaker') && in_array($type, ['health', 'mortality', 'per-cycle'], true)) {
                return true;
            }

            return false;
        });

        Gate::define('manage-report-schedules', function (User $user): bool {
            return in_array($user->role?->slug, ['system_admin', 'president', 'treasurer'], true);
        });

        Gate::define('view-reports-history', function (User $user): bool {
            return in_array($user->role?->slug, ['system_admin', 'president', 'treasurer', 'secretary', 'canvasser', 'caretaker'], true);
        });

        // Share role-filtered navigation with sidebar and app layout
        View::composer(['layouts.sidebar', 'layouts.app'], function ($view) {
            $user = auth()->user();

            if ($user) {
                $view->with('navigation', app(NavigationService::class)->forUser($user));
            } else {
                $view->with('navigation', [
                    'colors' => config('navigation.colors'),
                    'quick_action_colors' => config('navigation.quick_action_colors'),
                    'sections' => ['main' => ['label' => 'Main Menu', 'items' => []], 'quick_actions' => ['label' => 'Quick Actions', 'items' => []]],
                    'has_quick_actions' => false,
                    'has_main_items' => false,
                ]);
            }
        });
    }
}
