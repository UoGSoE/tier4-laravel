<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        \Illuminate\Support\Carbon::macro('outwith', static function ($start, $end) {
            return ! self::this()->between($start, $end);
        });

        \Illuminate\Support\Facades\Blade::if('admin', function () {
            return auth()->check() && auth()->user()->isAdmin();
        });
    }
}
