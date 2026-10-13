<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json($request->user()->notifications()->latest()->limit(50)->get());
    }

    public function read(Request $request, string $notification): JsonResponse
    {
        $item = $request->user()->notifications()->findOrFail($notification);
        $item->markAsRead();

        return response()->json($item->fresh());
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([], 204);
    }
}
