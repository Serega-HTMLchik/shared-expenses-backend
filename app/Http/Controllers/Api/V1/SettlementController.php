<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSettlementRequest;
use App\Models\Group;
use App\Models\Settlement;
use App\Services\SettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(Settlement::query()->where(fn ($query) => $query->where('from_user_id', $request->user()->id)->orWhere('to_user_id', $request->user()->id))->with(['group:id,name,currency', 'fromUser:id,name,email', 'toUser:id,name,email'])->latest()->get());
    }

    public function group(Request $request, Group $group): JsonResponse
    {
        $this->member($request, $group);

        return response()->json($group->settlements()->where(fn ($query) => $query->where('from_user_id', $request->user()->id)->orWhere('to_user_id', $request->user()->id))->with(['fromUser:id,name,email', 'toUser:id,name,email'])->latest()->get());
    }

    public function store(StoreSettlementRequest $request, Group $group, SettlementService $service): JsonResponse
    {
        $this->member($request, $group);

        return response()->json($service->create($group, $request->user(), $request->validated()), 201);
    }

    public function confirm(Request $request, Settlement $settlement, SettlementService $service): JsonResponse
    {
        return response()->json($service->confirm($settlement, $request->user()));
    }

    private function member(Request $request, Group $group): void
    {
        abort_unless($group->members()->where('user_id', $request->user()->id)->exists(), 404);
    }
}
