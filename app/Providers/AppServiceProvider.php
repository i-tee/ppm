<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\PartnerApplication;
use App\Observers\PartnerApplicationObserver;
use App\Observers\JoomlaOrderObserver;

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
        //
        PartnerApplication::observe(PartnerApplicationObserver::class);
    }
}
