<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserNotificationsChanged implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public User $user) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("users.{$this->user->id}")];
    }

    public function broadcastAs(): string
    {
        return 'shared-expenses.notifications.changed';
    }
}
