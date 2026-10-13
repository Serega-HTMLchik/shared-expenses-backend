<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Group;
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

        return response()->json($groups->flatMap(function (Group $group) use ($calculator, $userId): array {
            return collect($calculator->suggestedTransfers($group))
                ->filter(fn (array $transfer): bool => $transfer['from_user_id'] === $userId || $transfer['to_user_id'] === $userId)
                ->map(fn (array $transfer): array => $transfer + ['group_id' => $group->id, 'group_name' => $group->name, 'currency' => $group->currency])
                ->values()
                ->all();
        })->values());
    }
}
