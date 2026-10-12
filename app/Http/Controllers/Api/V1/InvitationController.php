<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptInvitationRequest;
use App\Http\Requests\StoreInvitationRequest;
use App\Models\Activity;
use App\Models\Group;
use App\Models\GroupInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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
            Mail::raw("You have been invited to join {$group->name}. Accept: {$acceptUrl}", function ($message) use ($invitation): void {
                $message->to($invitation->email)->subject('Group invitation');
            });
        }

        Activity::create(['group_id' => $group->id, 'actor_id' => $request->user()->id, 'type' => 'invitation.created', 'data' => ['invitation_id' => $invitation->id, 'email' => $invitation->email]]);

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

        return response()->json(['message' => 'Invitation accepted.']);
    }
}
