<?php

namespace App\Events;

use App\Models\Group;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SharedExpensesChanged implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public Group $group, public string $type) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return $this->group->members()
            ->pluck('user_id')
            ->map(fn (int $userId): PrivateChannel => new PrivateChannel("users.{$userId}"))
            ->all();
    }

    public function broadcastAs(): string
    {
        return 'shared-expenses.changed';
    }

    /** @return array{type: string, group_id: int} */
    public function broadcastWith(): array
    {
        return ['type' => $this->type, 'group_id' => $this->group->id];
    }
}
