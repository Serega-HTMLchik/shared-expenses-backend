<?php

namespace App\Services;

use App\Events\SharedExpensesChanged;
use App\Models\Activity;
use App\Models\Group;
use App\Models\Settlement;
use App\Models\User;
use App\Notifications\SettlementConfirmed;
use App\Notifications\SettlementPending;
use Illuminate\Support\Facades\DB;

class SettlementService
{
    public function __construct(private BalanceCalculator $calculator) {}

    /** @param array{to_user_id: int, amount_cents: int} $data */
    public function create(Group $group, User $debtor, array $data): Settlement
    {
        $settlement = DB::transaction(function () use ($group, $debtor, $data): Settlement {
            $group = Group::query()->lockForUpdate()->findOrFail($group->id);
            $memberIds = $group->members()->pluck('user_id');
            if (! $memberIds->contains($debtor->id) || ! $memberIds->contains($data['to_user_id'])) {
                abort(422, 'Settlement participants must belong to the group.');
            }

            $transfer = collect($this->calculator->suggestedTransfers($group))
                ->first(fn (array $item): bool => $item['from_user_id'] === $debtor->id && $item['to_user_id'] === $data['to_user_id']);
            $pending = (int) $group->settlements()->where('status', 'pending')->where('from_user_id', $debtor->id)->where('to_user_id', $data['to_user_id'])->sum('amount_cents');
            if ($data['amount_cents'] > (($transfer['amount_cents'] ?? 0) - $pending)) {
                abort(422, 'Settlement amount exceeds the outstanding debt.');
            }

            $settlement = $group->settlements()->create(['from_user_id' => $debtor->id, 'to_user_id' => $data['to_user_id'], 'amount_cents' => $data['amount_cents']]);
            Activity::create(['group_id' => $group->id, 'actor_id' => $debtor->id, 'type' => 'settlement.created', 'data' => ['settlement_id' => $settlement->id]]);
            $settlement->load(['fromUser:id,name,email', 'toUser:id,name,email', 'group:id,name,currency']);
            $settlement->toUser->notify(new SettlementPending($settlement));

            return $settlement;
        });

        SharedExpensesChanged::dispatch($group, 'settlement.created');

        return $settlement;
    }

    public function confirm(Settlement $settlement, User $recipient): Settlement
    {
        $settlement = DB::transaction(function () use ($settlement, $recipient): Settlement {
            $settlement = Settlement::query()->lockForUpdate()->findOrFail($settlement->id);
            abort_unless($settlement->to_user_id === $recipient->id, 403);
            abort_unless($settlement->status === 'pending', 409, 'Settlement is already processed.');
            $settlement->update(['status' => 'confirmed', 'confirmed_at' => now()]);
            Activity::create(['group_id' => $settlement->group_id, 'actor_id' => $recipient->id, 'type' => 'settlement.confirmed', 'data' => ['settlement_id' => $settlement->id]]);
            $settlement->load(['fromUser:id,name,email', 'toUser:id,name,email', 'group:id,name,currency']);
            $settlement->fromUser->notify(new SettlementConfirmed($settlement));

            return $settlement;
        });

        SharedExpensesChanged::dispatch($settlement->group, 'settlement.confirmed');

        return $settlement;
    }
}
