<?php

namespace App\Http\Controllers;

use App\Models\Month;
use Illuminate\Http\Request;

class MonthController extends Controller
{
    function index()
    {
        $months = Month::all();
        return response()->json($months, 200);
    }

    function show($id)
    {
        $month = Month::find($id);
        if(!$month) {
            return response()->json(['message'=>'Month not found'], 404);
        }

        return response()->json($month, 200);
    }

    function store()
    {
        $exists = Month::where('year', now()->year)
                        ->where('month_number', now()->month)
                        ->exists();

        if($exists)
        {
            return;

        }
        
        $ActiveMonth = Month::where('is_active', true)->first();

        $income = $ActiveMonth ? $ActiveMonth->income : 0;
        $savings_goal = $ActiveMonth ? $ActiveMonth->savings_goal : 0;

        $month = Month::forceCreate([
            'year' => now()->year,
            'month_number' => now()->month,
            'income' => $income,
            'savings_goal' => $savings_goal,
            'is_active' => true,
        ]);

        if($ActiveMonth)
        {
            $ActiveMonth->is_active = false;
            $ActiveMonth->save();
        }

        return response()->json($month, 201);
        
    }

    function update(Request $request, $id)
    {
        $month = Month::find($id);
        if(!$month) {
            return response()->json(['message'=>'Month not found'], 404);
        }

        if(!$month->is_active) {
            return response()->json(['message'=>'Cannot update a past month'], 403);
        }

        $request->validate([
            'income'=> 'required|numeric',
            'savings_goal'=> 'required|numeric',
        ]);

        $month->update([
            'income'=> $request->income,
            'savings_goal'=> $request->savings_goal,
        ]);

        return response()->json($month, 200);
    }

    public function summary($id)
    {
        $month = Month::find($id);
        if(!$month) {
            return response()->json(['message'=>'Month not found'], 404);
        }

        return response()->json($month->summary(), 200);
    }
}
