<?php

namespace App\Notifications;

use App\Models\Settlement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SettlementPending extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Settlement $settlement) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return ['kind' => 'settlement.pending', 'title' => 'Ожидает подтверждения погашение', 'body' => "{$this->settlement->fromUser->name} отметил перевод в группе «{$this->settlement->group->name}».", 'group_id' => $this->settlement->group_id, 'settlement_id' => $this->settlement->id];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Подтвердите погашение долга')->line("{$this->settlement->fromUser->name} указал перевод {$this->settlement->amount_cents} коп.")->line("Группа: {$this->settlement->group->name}");
    }
}
