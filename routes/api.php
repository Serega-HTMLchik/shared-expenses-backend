<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BalanceController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\GroupController;
use App\Http\Controllers\Api\V1\InvitationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::get('invitations/{token}', [InvitationController::class, 'show']);
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::apiResource('groups', GroupController::class)->only(['index', 'store', 'show']);
        Route::post('groups/{group}/close', [GroupController::class, 'close']);
        Route::post('groups/{group}/reopen', [GroupController::class, 'reopen']);
        Route::post('groups/{group}/invitations', [InvitationController::class, 'store']);
        Route::post('invitations/{token}/accept', [InvitationController::class, 'accept']);
        Route::get('groups/{group}/expenses', [ExpenseController::class, 'index']);
        Route::post('groups/{group}/expenses', [ExpenseController::class, 'store']);
        Route::put('groups/{group}/expenses/{expense}', [ExpenseController::class, 'update']);
        Route::delete('groups/{group}/expenses/{expense}', [ExpenseController::class, 'destroy']);
        Route::get('groups/{group}/balances', [BalanceController::class, 'group']);
        Route::get('balances', [BalanceController::class, 'all']);
    });
});
