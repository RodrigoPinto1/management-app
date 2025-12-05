<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\LogSuccessfulLogout;
use Inertia\Inertia;
use App\Models\Company;

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
        // Register event listeners for activity logging
        Event::listen(Login::class, LogSuccessfulLogin::class);
        Event::listen(Logout::class, LogSuccessfulLogout::class);

        // Share company settings with all Inertia responses
        try {
            $company = Company::first();
        } catch (\Throwable $e) {
            $company = null;
        }

        Inertia::share('company', $company ? $company->toArray() : null);
    }
}
