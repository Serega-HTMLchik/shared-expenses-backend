<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Expense;
use App\Models\Group;
use App\Models\User;
use App\Notifications\ExpenseInvolved;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    /** @param array<string, mixed> $data */
    public function save(Group $group, User $actor, array $data, ?Expense $expense = null): Expense
    {
        return DB::transaction(function () use ($group, $actor, $data, $expense): Expense {
            $group->lockForUpdate()->findOrFail($group->id);
            if ($group->closed_at !== null) {
                abort(409, 'Group is closed.');
            }
            $members = $group->members()->with('user:id,email')->get()->keyBy('user_id');
            foreach ($data['shares'] as $share) {
                if (! $members->has($share['user_id'])) {
                    abort(422, 'Every share recipient must be a group member.');
                }
            }
            if (! $members->has($data['payer_id'])) {
                abort(422, 'Payer must be a group member.');
            }
            $payload = collect($data)->except(['shares', 'receipt'])->all();
            if (isset($data['receipt'])) {
                $payload['receipt_path'] = $data['receipt']->store('receipts');
            }
            if ($expense === null) {
                $expense = $group->expenses()->create($payload + ['created_by_id' => $actor->id]);
            } else {
                $expense->update($payload);
                $expense->shares()->delete();
            }
            $shares = collect($data['shares'])->sortBy(fn (array $share): string => $members[$share['user_id']]->user->email)->values();
            $amounts = $this->split((int) $data['amount_cents'], $data['split_type'], $shares->all());
            foreach ($shares as $index => $share) {
                $expense->shares()->create(['user_id' => $share['user_id'], 'amount_cents' => $amounts[$index], 'units' => $share['units'] ?? null]);
            }
            Activity::create(['group_id' => $group->id, 'actor_id' => $actor->id, 'type' => $expense->wasRecentlyCreated ? 'expense.created' : 'expense.updated', 'data' => ['expense_id' => $expense->id]]);
            User::whereIn('id', $shares->pluck('user_id'))->get()->each->notify(new ExpenseInvolved($expense));

            return $expense->load(['payer:id,name,email', 'shares.user:id,name,email']);
        });
    }

    /** @param array<int, array<string, mixed>> $shares @return array<int, int> */
    private function split(int $total, string $type, array $shares): array
    {
        if ($type === 'exact') {
            $amounts = array_map(fn (array $share): int => (int) $share['amount_cents'], $shares);
            if (array_sum($amounts) !== $total) {
                abort(422, 'Exact shares must equal the expense amount.');
            }

return $amounts;
        }
        $weights = $type === 'shares' ? array_map(fn (array $share): int => (int) $share['units'], $shares) : array_fill(0, count($shares), 1);
        if (array_sum($weights) < 1) {
            abort(422, 'At least one positive share is required.');
        }
        $result = [];
        $remainder = $total;
        foreach ($weights as $weight) {
            $amount = intdiv($total * $weight, array_sum($weights));
            $result[] = $amount;
            $remainder -= $amount;
        }
        for ($index = 0; $remainder > 0; $index++, $remainder--) {
            $result[$index % count($result)]++;
        }

        return $result;
    }
}
