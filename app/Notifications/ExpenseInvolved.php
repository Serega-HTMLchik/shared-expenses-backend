<?php

namespace App\Notifications;

use App\Models\Expense;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpenseInvolved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Expense $expense) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return ['expense_id' => $this->expense->id, 'group_id' => $this->expense->group_id, 'amount_cents' => $this->expense->amount_cents];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('New shared expense')->line('You are included in an expense of '.$this->expense->amount_cents.' cents.');
    }
}
