<?php

namespace App\Http\Controllers;

use App\Http\Resources\MonthResource;
use App\Http\Resources\MonthSummaryResource;
use App\Models\Month;
use Illuminate\Http\Request;

class MonthController extends Controller
{
    // Index method
    public function index()
    {
        $months = auth()->user()->months()->get();

        return MonthResource::collection($months);
    }

    // Show method
    public function show($id)
    {
        $month = auth()->user()->months()->find($id);
        if (! $month) {
            return response()->json(['message' => 'Month not found'], 404);
        }

        return new MonthResource($month);
    }

    // Store method (used by EnsureCurrentMonthExists middleware)
    public function store()
    {
        $exists = auth()->user()->months()
            ->where('year', now()->year)
            ->where('month_number', now()->month)
            ->exists();

        if ($exists) {
            return;
        }

        $activeMonth = auth()->user()->months()
            ->where('is_active', true)
            ->first();

        $income = $activeMonth ? $activeMonth->income : 0;
        $savings_goal = $activeMonth ? $activeMonth->savings_goal : 0;

        $month = Month::forceCreate([
            'year' => now()->year,
            'month_number' => now()->month,
            'income' => $income,
            'savings_goal' => $savings_goal,
            'is_active' => true,
            'user_id' => auth()->id(),
        ]);

        if ($activeMonth) {
            $activeMonth->is_active = false;
            $activeMonth->save();
        }

        return response()->json($month, 201);
    }

    // Update method
    public function update(Request $request, $id)
    {
        $month = auth()->user()->months()->find($id);
        if (! $month) {
            return response()->json(['message' => 'Month not found'], 404);
        }

        if (! $month->is_active) {
            return response()->json(['message' => 'Cannot update a past month'], 403);
        }

        $request->validate([
            'income' => 'required|numeric',
            'savings_goal' => 'required|numeric',
        ]);

        $month->update([
            'income' => $request->income,
            'savings_goal' => $request->savings_goal,
        ]);

        return new MonthResource($month);
    }

    // summary method
    public function summary($id)
    {
        $month = auth()->user()->months()->find($id);
        if (! $month) {
            return response()->json(['message' => 'Month not found'], 404);
        }

        return new MonthSummaryResource($month);
    }
}
