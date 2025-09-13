<?php

namespace App\Providers;

use App\Models\Backend\Notification;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            $activeNotifications = Notification::active()->get();
            $view->with('activeNotifications', $activeNotifications);
        });
    }
}
