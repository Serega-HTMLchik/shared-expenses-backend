<?php

namespace App\Providers;

use App\Events\UserNotificationsChanged;
use App\Models\User;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
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
        Event::listen(NotificationSent::class, function (NotificationSent $event): void {
            if ($event->channel === 'database' && $event->notifiable instanceof User) {
                UserNotificationsChanged::dispatch($event->notifiable);
            }
        });
    }
}
