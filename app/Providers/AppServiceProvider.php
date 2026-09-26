<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\Store;
use App\Policies\CompanyPolicy;
use App\Policies\StorePolicy;
use Illuminate\Pagination\Paginator;
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
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Store::class, StorePolicy::class);

        // Pagination
        Paginator::useBootstrapFive();
        Paginator::useBootstrapFour();
    }
}
