<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
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

            if ($user->hasRole('treasurer') && in_array($type, ['expense', 'sales', 'monthly', 'quarterly', 'profitability'], true)) {
                return true;
            }

            if ($user->hasRole('secretary') && in_array($type, ['inventory', 'health', 'mortality'], true)) {
                return true;
            }

            return false;
        });

        Gate::define('manage-report-schedules', function (User $user): bool {
            return in_array($user->role?->slug, ['system_admin', 'president', 'treasurer'], true);
        });

        Gate::define('view-reports-history', function (User $user): bool {
            return in_array($user->role?->slug, ['system_admin', 'president', 'treasurer', 'secretary'], true);
        });
    }
}
