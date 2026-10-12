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
}
