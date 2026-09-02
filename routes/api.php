<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\MonthController;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::middleware('ensure.month')->group(function () {
        Route::apiResource('months', MonthController::class)->except(['store', 'destroy']);
        Route::get('months/{id}/summary', [MonthController::class, 'summary']);
    });

    // Category routes
    Route::apiResource('categories', CategoryController::class)->except(['show']);

    // Expense routes
    Route::apiResource('expenses', ExpenseController::class)->except(['index', 'store']);
    Route::get('months/{monthId}/expenses', [ExpenseController::class, 'index']);
    Route::post('months/{monthId}/expenses', [ExpenseController::class, 'store']);
});
