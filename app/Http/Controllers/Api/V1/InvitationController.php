<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\SharedExpensesChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptInvitationRequest;
use App\Http\Requests\StoreInvitationRequest;
use App\Models\Activity;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Notifications\GroupInvitationReceived;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    public function show(string $token): JsonResponse
    {
        $invitation = GroupInvitation::with('group:id,name,currency,closed_at')->where('token', $token)->firstOrFail();
        abort_if($invitation->accepted_at !== null || $invitation->expires_at->isPast(), 410, 'Invitation is no longer valid.');

        return response()->json(['group' => $invitation->group, 'expires_at' => $invitation->expires_at]);
    }

    public function store(StoreInvitationRequest $request, Group $group): JsonResponse
    {
        abort_unless($group->owner_id === $request->user()->id, 403);

        $data = $request->validated();
        $invitation = GroupInvitation::create([
            'group_id' => $group->id,
            'invited_by_id' => $request->user()->id,
            'email' => $data['email'] ?? null,
            'token' => Str::random(48),
            'expires_at' => now()->addDays($data['expires_in_days'] ?? 7),
        ]);
        $acceptUrl = rtrim((string) config('app.frontend_url'), '/').'/invitations/'.$invitation->token;

        if ($invitation->email !== null) {
            Notification::route('mail', $invitation->email)->notify(new GroupInvitationReceived($group, $acceptUrl));
        }

        Activity::create(['group_id' => $group->id, 'actor_id' => $request->user()->id, 'type' => 'invitation.created', 'data' => ['invitation_id' => $invitation->id, 'email' => $invitation->email]]);
        SharedExpensesChanged::dispatch($group, 'invitation.created');

        return response()->json(['invitation' => $invitation, 'accept_url' => $acceptUrl], 201);
    }

    public function accept(AcceptInvitationRequest $request, string $token): JsonResponse
    {
        $invitation = GroupInvitation::where('token', $token)->firstOrFail();
        abort_if($invitation->accepted_at !== null || $invitation->expires_at->isPast(), 410, 'Invitation is no longer valid.');
        abort_if($invitation->email !== null && $invitation->email !== $request->user()->email, 403, 'Invitation belongs to another email address.');

        DB::transaction(function () use ($invitation, $request): void {
            $invitation->update(['accepted_at' => now()]);
            $invitation->group->members()->firstOrCreate(['user_id' => $request->user()->id]);
            Activity::create(['group_id' => $invitation->group_id, 'actor_id' => $request->user()->id, 'type' => 'invitation.accepted', 'data' => ['invitation_id' => $invitation->id]]);
        });
        SharedExpensesChanged::dispatch($invitation->group, 'invitation.accepted');

        return response()->json(['message' => 'Invitation accepted.']);
    }
}
