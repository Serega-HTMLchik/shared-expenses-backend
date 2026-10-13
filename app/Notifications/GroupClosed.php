<?php

namespace App\Notifications;

use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupClosed extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param array<int, array<string, mixed>> $transfers */
    public function __construct(public Group $group, public array $transfers) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return ['kind' => 'group.closed', 'title' => 'Группа закрыта', 'body' => "Группа «{$this->group->name}» закрыта. Итоговый отчёт доступен в приложении.", 'group_id' => $this->group->id, 'suggested_transfers' => $this->transfers];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject("Группа «{$this->group->name}» закрыта")->line('Новые расходы больше не принимаются.')->line('Итоговый отчёт доступен в приложении.');
    }
}
