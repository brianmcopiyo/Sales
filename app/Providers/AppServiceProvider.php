<?php

namespace App\Providers;

use App\Models\BranchStock;
use App\Observers\BranchStockObserver;
use App\Services\NavigationService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register NavigationService as singleton
        $this->app->singleton(NavigationService::class, function ($app) {
            return new NavigationService();
        });
    }

    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.simple-tailwind');
        Paginator::defaultSimpleView('vendor.pagination.simple-tailwind');

        // Share NavigationService with all views
        View::share('navigation', $this->app->make(NavigationService::class));

        // Share currency from config (.env: APP_CURRENCY_CODE, APP_CURRENCY_SYMBOL, APP_CURRENCY_NAME)
        View::share('currencySymbol', config('app.currency_symbol'));
        View::share('currencyCode', config('app.currency_code'));
        View::share('currencyName', config('app.currency_name'));

        BranchStock::observe(BranchStockObserver::class);

        // Custom Blade directive: show content only if user has permission (avoids @can() issues)
        Blade::if('hasPermission', function (string $permission) {
            $user = auth()->user();
            return $user && method_exists($user, 'hasPermission') && $user->hasPermission($permission);
        });

        // Make Blade @can() use the app's permission system (User::hasPermission + isAdmin bypass)
        Gate::before(function ($user, string $ability) {
            if (!$user) {
                return null;
            }
            $user->loadMissing('roleModel');
            if (method_exists($user, 'hasPermission')) {
                return $user->hasPermission($ability) ? true : false;
            }
            return null;
        });
    }
}
