<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\Store;
use App\Policies\CompanyPolicy;
use App\Policies\StorePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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

        Paginator::useBootstrapFive();
        Paginator::useBootstrapFour();

        RateLimiter::for('contacto', function (Request $request) {
            $limits = [
                Limit::perMinute(3)->by('contacto-ip:'.$request->ip()),
                Limit::perHour(20)->by('contacto-hour:'.$request->ip()),
            ];

            $email = strtolower(trim((string) $request->input('email')));
            if ($email !== '') {
                $limits[] = Limit::perHour(8)->by('contacto-email:'.$email);
            }

            return $limits;
        });
    }
}
