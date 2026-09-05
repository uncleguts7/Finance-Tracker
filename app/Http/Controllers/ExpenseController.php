<?php

namespace App\Http\Controllers;

use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    // Index method
    public function index($monthId)
    {
        $month = auth()->user()->months()->find($monthId);
        if (! $month) {
            return response()->json(['message' => 'Month not found'], 404);
        }

        $expenses = Expense::where('month_id', $monthId)->get();

        return ExpenseResource::collection($expenses);
    }

    // Show method
    public function show($id)
    {
        $expense = Expense::whereHas('month', function ($query) {
            $query->where('user_id', auth()->id());
        })->find($id);

        if (! $expense) {
            return response()->json(['message' => 'Expense not found'], 404);
        }

        return new ExpenseResource($expense);
    }

    // Store method
    public function store(Request $request, $monthId)
    {
        $month = auth()->user()->months()->find($monthId);
        if (! $month) {
            return response()->json(['message' => 'Month not found'], 404);
        }

        if (! $month->is_active) {
            return response()->json(['message' => 'Cannot add expense to a past month'], 403);
        }

        $request->validate([
            'amount' => 'required|numeric',
            'date' => 'nullable|date',
            'type' => 'required|string',
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')
                    ->where('user_id', auth()->id())
                    ->whereNull('deleted_at'),
            ],
        ]);

        $expense = new Expense([
            'amount' => $request->amount,
            'date' => $request->date ?? now(),
            'type' => $request->type,
            'category_id' => $request->category_id,
        ]);

        $expense->month_id = $monthId;
        $expense->save();

        return ExpenseResource::make($expense)->response()->setStatusCode(201);
    }

    // Update method
    public function update(Request $request, $id)
    {
        $expense = Expense::whereHas('month', function ($query) {
            $query->where('user_id', auth()->id());
        })->find($id);

        if (! $expense) {
            return response()->json(['message' => 'Expense not found'], 404);
        }

        if (! $expense->month->is_active) {
            return response()->json(['message' => 'Cannot update an expense from a past month'], 403);
        }

        $request->validate([
            'amount' => 'required|numeric',
            'date' => 'nullable|date',
            'type' => 'required|string',
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')
                    ->where('user_id', auth()->id())
                    ->whereNull('deleted_at'),
            ],
        ]);

        $expense->update([
            'amount' => $request->amount,
            'date' => $request->date ?? now(),
            'type' => $request->type,
            'category_id' => $request->category_id,
        ]);

        return new ExpenseResource($expense);
    }

    // Destroy method
    public function destroy($id)
    {
        $expense = Expense::whereHas('month', function ($query) {
            $query->where('user_id', auth()->id());
        })->find($id);

        if (! $expense) {
            return response()->json(['message' => 'Expense not found'], 404);
        }

        if (! $expense->month->is_active) {
            return response()->json(['message' => 'Cannot delete an expense from a past month'], 403);
        }

        $expense->delete();

        return response()->json(['message' => 'Expense deleted successfully'], 200);
    }
}
