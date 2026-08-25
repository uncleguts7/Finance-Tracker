<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MonthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('ensure.month')->group(function() {
    Route::apiResource('months', MonthController::class)->except(['store', 'destroy']);
});

Route::apiResource('categories', CategoryController::class)->except(['show']);
