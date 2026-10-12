<?php

use App\Http\Controllers\Api\V1\BalanceController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\GroupController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('api.user')->group(function (): void {
    Route::apiResource('groups', GroupController::class)->only(['index', 'store', 'show']);
    Route::post('groups/{group}/close', [GroupController::class, 'close']);
    Route::post('groups/{group}/reopen', [GroupController::class, 'reopen']);
    Route::get('groups/{group}/expenses', [ExpenseController::class, 'index']);
    Route::post('groups/{group}/expenses', [ExpenseController::class, 'store']);
    Route::put('groups/{group}/expenses/{expense}', [ExpenseController::class, 'update']);
    Route::delete('groups/{group}/expenses/{expense}', [ExpenseController::class, 'destroy']);
    Route::get('groups/{group}/balances', [BalanceController::class, 'group']);
    Route::get('balances', [BalanceController::class, 'all']);
});
