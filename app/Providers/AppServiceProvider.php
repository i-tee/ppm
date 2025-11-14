<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\PartnerApplication;
use App\Models\User;
use App\Observers\PartnerApplicationObserver;
use App\Observers\UserObserver;
use App\Observers\JoomlaOrderObserver;
use App\Observers\RequisiteObserver;
use App\Models\Requisite;

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
        User::observe(UserObserver::class);
        Requisite::observe(RequisiteObserver::class);
    }
}
