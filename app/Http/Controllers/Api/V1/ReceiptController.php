<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReceiptRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ReceiptController extends Controller
{
    public function store(StoreReceiptRequest $request): JsonResponse
    {
        $path = $request->file('receipt')->store('receipts', 'public');

        return response()->json(['url' => Storage::disk('public')->url($path)], 201);
    }
}
