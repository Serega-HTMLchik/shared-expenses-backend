<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\SharedExpensesChanged;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Group;
use App\Notifications\GroupClosed;
use App\Services\BalanceCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GroupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json($request->user()->groupMemberships()->with('group')->get()->pluck('group'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'currency' => ['required', 'string', 'size:3'], 'reminder_after_days' => ['nullable', 'integer', 'min:1', 'max:365']]);
        $group = DB::transaction(function () use ($data, $request): Group {
            $group = Group::create($data + ['owner_id' => $request->user()->id, 'invite_token' => Str::random(48)]);
            $group->members()->create(['user_id' => $request->user()->id]);
            Activity::create(['group_id' => $group->id, 'actor_id' => $request->user()->id, 'type' => 'group.created']);

            return $group;
        });
        SharedExpensesChanged::dispatch($group, 'group.created');

        return response()->json($group, 201);
    }

    public function show(Request $request, Group $group): JsonResponse
    {
        $this->member($request, $group);

        return response()->json($group->load('members.user:id,name,email'));
    }

    public function close(Request $request, Group $group, BalanceCalculator $calculator): JsonResponse
    {
        abort_unless($group->owner_id === $request->user()->id, 403);
        $group->update(['closed_at' => now()]);

        $transfers = $calculator->suggestedTransfers($group);
        $group->members()->with('user')->get()->each(fn ($member) => $member->user->notify(new GroupClosed($group, $transfers)));
        SharedExpensesChanged::dispatch($group, 'group.closed');

        return response()->json(['group' => $group, 'report' => ['balances' => $calculator->netBalances($group), 'suggested_transfers' => $transfers]]);
    }

    public function reopen(Request $request, Group $group): JsonResponse
    {
        abort_unless($group->owner_id === $request->user()->id, 403);
        $group->update(['closed_at' => null]);
        SharedExpensesChanged::dispatch($group, 'group.reopened');

        return response()->json($group);
    }

    public function report(Request $request, Group $group, BalanceCalculator $calculator): JsonResponse
    {
        $this->member($request, $group);

        return response()->json(['group' => $group, 'balances' => $calculator->netBalances($group), 'suggested_transfers' => $calculator->suggestedTransfers($group)]);
    }

    private function member(Request $request, Group $group): void
    {
        abort_unless($group->members()->where('user_id', $request->user()->id)->exists(), 404);
    }
}
