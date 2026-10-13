<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use App\Services\BalanceCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    public function group(Request $request, Group $group, BalanceCalculator $calculator): JsonResponse
    {
        abort_unless($group->members()->where('user_id', $request->user()->id)->exists(), 404);

        return response()->json(['balances' => $calculator->netBalances($group), 'suggested_transfers' => $calculator->suggestedTransfers($group)]);
    }

    public function all(Request $request, BalanceCalculator $calculator): JsonResponse
    {
        $groups = $request->user()->groupMemberships()->with('group')->get()->pluck('group');

        return response()->json($groups->map(fn (Group $group): array => ['group_id' => $group->id, 'balances' => $calculator->netBalances($group), 'suggested_transfers' => $calculator->suggestedTransfers($group)]));
    }

    public function debts(Request $request, BalanceCalculator $calculator): JsonResponse
    {
        $userId = $request->user()->id;
        $groups = $request->user()->groupMemberships()->with('group')->get()->pluck('group');

        $transfers = $groups->groupBy('currency')->flatMap(function ($currencyGroups, string $currency) use ($calculator, $userId): array {
            $balances = $currencyGroups->reduce(function (array $result, Group $group) use ($calculator): array {
                foreach ($calculator->netBalances($group) as $memberId => $amount) {
                    $result[$memberId] = ($result[$memberId] ?? 0) + $amount;
                }

                return $result;
            }, []);

            return collect($calculator->suggestedTransfersForBalances($balances))
                ->filter(fn (array $transfer): bool => $transfer['from_user_id'] === $userId)
                ->map(fn (array $transfer): array => $transfer + ['group_name' => 'Общий баланс', 'currency' => $currency])
                ->values()
                ->all();
        })->values();
        $users = User::query()->whereIn('id', $transfers->pluck('to_user_id')->push($userId))->get(['id', 'name', 'email'])->keyBy('id');

        return response()->json($transfers->map(fn (array $transfer): array => $transfer + ['from_user' => $users[$transfer['from_user_id']], 'to_user' => $users[$transfer['to_user_id']]]));
    }

    public function receivables(Request $request, BalanceCalculator $calculator): JsonResponse
    {
        $userId = $request->user()->id;
        $groups = $request->user()->groupMemberships()->with('group')->get()->pluck('group');

        $transfers = $groups->groupBy('currency')->flatMap(function ($currencyGroups, string $currency) use ($calculator, $userId): array {
            $balances = $currencyGroups->reduce(function (array $result, Group $group) use ($calculator): array {
                foreach ($calculator->netBalances($group) as $memberId => $amount) {
                    $result[$memberId] = ($result[$memberId] ?? 0) + $amount;
                }

                return $result;
            }, []);

            return collect($calculator->suggestedTransfersForBalances($balances))
                ->filter(fn (array $transfer): bool => $transfer['to_user_id'] === $userId)
                ->map(fn (array $transfer): array => $transfer + ['group_name' => 'Общий баланс', 'currency' => $currency])
                ->values()
                ->all();
        })->values();
        $users = User::query()->whereIn('id', $transfers->pluck('from_user_id')->push($userId))->get(['id', 'name', 'email'])->keyBy('id');

        return response()->json($transfers->map(fn (array $transfer): array => $transfer + ['from_user' => $users[$transfer['from_user_id']], 'to_user' => $users[$transfer['to_user_id']]]));
    }
}
