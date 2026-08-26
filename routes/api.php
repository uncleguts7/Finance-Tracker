<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MonthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExpenseController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('ensure.month')->group(function() {
    Route::apiResource('months', MonthController::class)->except(['store', 'destroy']);
});

//Category routes
Route::apiResource('categories', CategoryController::class)->except(['show']);

//Expense routes
Route::apiResource('expenses', ExpenseController::class)->except(['index', 'store']);
Route::get('months/{monthId}/expenses', [ExpenseController::class, 'index']);
Route::post('months/{monthId}/expenses', [ExpenseController::class, 'store']);
