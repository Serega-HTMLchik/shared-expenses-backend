<?php

namespace App\Notifications;

use App\Models\Group;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DebtReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Group $group, public User $recipient, public int $amountCents) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return ['kind' => 'debt.reminder', 'title' => 'Напоминание о долге', 'body' => "Вы должны {$this->amount()} пользователю {$this->recipient->name} в группе «{$this->group->name}».", 'group_id' => $this->group->id, 'to_user_id' => $this->recipient->id, 'amount_cents' => $this->amountCents];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Напоминание о долге')->line("В группе «{$this->group->name}» у вас остался долг {$this->amount()} получателю {$this->recipient->name}.");
    }

    private function amount(): string
    {
        return number_format($this->amountCents / 100, 2, ',', ' ').' '.$this->group->currency;
    }
}
