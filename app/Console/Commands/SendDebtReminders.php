<?php

namespace App\Console\Commands;

use App\Models\DebtReminder;
use App\Models\Group;
use App\Models\User;
use App\Notifications\DebtReminderNotification;
use App\Services\BalanceCalculator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

#[Signature('app:send-debt-reminders')]
#[Description('Send weekly reminders for overdue group debts')]
class SendDebtReminders extends Command
{
    public function handle(BalanceCalculator $calculator): int
    {
        Group::query()->each(function (Group $group) use ($calculator): void {
            foreach ($calculator->suggestedTransfers($group) as $transfer) {
                $hasOverdueShare = $group->expenses()
                    ->whereDate('spent_on', '<=', now()->subDays($group->reminder_after_days))
                    ->whereHas('shares', fn (Builder $query): Builder => $query->where('user_id', $transfer['from_user_id']))
                    ->exists();
                if (! $hasOverdueShare) {
                    continue;
                }
                $reminder = DebtReminder::query()->firstOrNew(['group_id' => $group->id, 'from_user_id' => $transfer['from_user_id'], 'to_user_id' => $transfer['to_user_id']]);
                if ($reminder->last_sent_at?->gt(now()->subWeek())) {
                    continue;
                }
                $debtor = User::find($transfer['from_user_id']);
                $recipient = User::find($transfer['to_user_id']);
                if ($debtor === null || $recipient === null) {
                    continue;
                }
                $debtor->notify(new DebtReminderNotification($group, $recipient, $transfer['amount_cents']));
                $reminder->last_sent_at = now();
                $reminder->save();
            }
        });

        return self::SUCCESS;
    }
}
