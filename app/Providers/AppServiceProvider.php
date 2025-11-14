<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\PartnerApplication;
use App\Models\User;
use App\Models\Requisite;
use App\Observers\PartnerApplicationObserver;
use App\Observers\UserObserver;
use App\Observers\RequisiteObserver;


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
