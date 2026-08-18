<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\Tenancy\TenantConnectionResolver::class);

        $this->app->bind(
            \App\Services\BranchRequests\BranchPricingService::class,
            \App\Services\BranchRequests\FlatBranchPricingService::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(database_path('migrations/tenant'));

        Blade::anonymousComponentPath(resource_path('views/components/payroll'), 'payroll');

        Blade::directive('money', function (string $expression) {
            return "<?php echo format_money($expression); ?>";
        });

        Gate::before(function ($user) {
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return true;
            }
        });
    }
}
