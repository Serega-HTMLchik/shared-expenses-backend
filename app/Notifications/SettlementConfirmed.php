<?php

namespace App\Notifications;

use App\Models\Settlement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SettlementConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Settlement $settlement) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return ['kind' => 'settlement.confirmed', 'title' => 'Погашение подтверждено', 'body' => "{$this->settlement->toUser->name} подтвердил получение перевода.", 'group_id' => $this->settlement->group_id, 'settlement_id' => $this->settlement->id];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Погашение подтверждено')->line("{$this->settlement->toUser->name} подтвердил перевод в группе «{$this->settlement->group->name}». ");
    }
}
