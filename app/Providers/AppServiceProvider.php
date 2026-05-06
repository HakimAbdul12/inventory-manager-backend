<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;

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
        \App\Models\InventoryItem::observe(\App\Observers\InventoryItemObserver::class);

        ResetPassword::createUrlUsing(function ($user, string $token) {
            return config('app.frontend_url') . '/auth/reset-password?token=' . $token . '&email=' . $user->email;
        });
    }
}
