<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Produccion;
use App\Observers\ProduccionObserver;

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
        Produccion::observe(ProduccionObserver::class);
    }
}
