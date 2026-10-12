<?php

namespace App\Services;

use App\Models\Group;

class BalanceCalculator
{
    /** @return array<int, int> */
    public function netBalances(Group $group): array
    {
        $balances = $group->members()->pluck('user_id')->mapWithKeys(fn (int $id): array => [$id => 0])->all();
        $group->expenses()->with('shares')->get()->each(function ($expense) use (&$balances): void {
            $balances[$expense->payer_id] += $expense->amount_cents;
            foreach ($expense->shares as $share) {
                $balances[$share->user_id] -= $share->amount_cents;
            }
        });
        $group->settlements()->where('status', 'confirmed')->get()->each(function ($settlement) use (&$balances): void {
            $balances[$settlement->from_user_id] += $settlement->amount_cents;
            $balances[$settlement->to_user_id] -= $settlement->amount_cents;
        });

        return $balances;
    }

    /** @return array<int, array{from_user_id:int,to_user_id:int,amount_cents:int}> */
    public function suggestedTransfers(Group $group): array
    {
        $balances = $this->netBalances($group);
        $creditors = [];
        $debtors = [];
        foreach ($balances as $userId => $amount) {
            if ($amount > 0) {
                $creditors[$userId] = $amount;
            } if ($amount < 0) {
                $debtors[$userId] = -$amount;
            }
        }
        $transfers = [];
        while ($creditors !== [] && $debtors !== []) {
            $creditorId = array_key_first($creditors);
            $debtorId = array_key_first($debtors);
            $amount = min($creditors[$creditorId], $debtors[$debtorId]);
            $transfers[] = ['from_user_id' => $debtorId, 'to_user_id' => $creditorId, 'amount_cents' => $amount];
            $creditors[$creditorId] -= $amount;
            $debtors[$debtorId] -= $amount;
            if ($creditors[$creditorId] === 0) {
                unset($creditors[$creditorId]);
            } if ($debtors[$debtorId] === 0) {
                unset($debtors[$debtorId]);
            }
        }

        return $transfers;
    }
}
