<?php

namespace App\Http\Controllers;

use App\Http\Resources\MonthResource;
use App\Http\Resources\MonthSummaryResource;
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
