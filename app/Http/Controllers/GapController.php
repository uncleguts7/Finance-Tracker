<?php

namespace App\Http\Controllers;

use App\Models\Month;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GapController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth()->user();

        $earliest = $user->months()
            ->orderBy('year')
            ->orderBy('month_number')
            ->first();

        if (! $earliest) {
            return response()->json(['gaps' => []]);
        }

        $existing = $user->months()
            ->selectRaw('year, month_number')
            ->get()
            ->map(fn ($m) => $m->year.'-'.$m->month_number)
            ->values()
            ->all();

        $gaps = [];
        $cursor = Carbon::create($earliest->year, $earliest->month_number, 1);
        $current = Carbon::now()->startOfMonth();

        while ($cursor->lte($current)) {
            $key = $cursor->year.'-'.$cursor->month;
            if (! in_array($key, $existing, true)) {
                $gaps[] = [
                    'year' => $cursor->year,
                    'month_number' => $cursor->month,
                    'label' => $cursor->format('F Y'),
                ];
            }
            $cursor->addMonthNoOverflow();
        }

        return response()->json(['gaps' => $gaps]);
    }

    public function backfill(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:2099',
            'month_number' => 'required|integer|min:1|max:12',
            'income' => 'required|numeric|min:0',
            'savings_goal' => 'required|numeric|min:0',
        ]);

        $user = auth()->user();

        $exists = $user->months()
            ->where('year', $validated['year'])
            ->where('month_number', $validated['month_number'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'This month already exists'], 422);
        }

        $month = Month::forceCreate([
            'year' => $validated['year'],
            'month_number' => $validated['month_number'],
            'income' => $validated['income'],
            'savings_goal' => $validated['savings_goal'],
            'is_active' => false,
            'is_skipped' => false,
            'user_id' => $user->id,
        ]);

        return response()->json($month, 201);
    }

    public function skip(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:2099',
            'month_number' => 'required|integer|min:1|max:12',
        ]);

        $user = auth()->user();

        $exists = $user->months()
            ->where('year', $validated['year'])
            ->where('month_number', $validated['month_number'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'This month already exists'], 422);
        }

        $month = Month::forceCreate([
            'year' => $validated['year'],
            'month_number' => $validated['month_number'],
            'income' => 0,
            'savings_goal' => 0,
            'is_active' => false,
            'is_skipped' => true,
            'skipped_at' => now(),
            'user_id' => $user->id,
        ]);

        return response()->json($month, 201);
    }
}
