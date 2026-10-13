<?php

namespace App\Notifications;

use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupInvitationReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Group $group, public string $acceptUrl) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Приглашение в группу')
            ->greeting('Здравствуйте!')
            ->line("Вас пригласили присоединиться к группе «{$this->group->name}».")
            ->action('Принять приглашение', $this->acceptUrl)
            ->line('Если вы не ожидали это приглашение, просто проигнорируйте письмо.');
    }
}
