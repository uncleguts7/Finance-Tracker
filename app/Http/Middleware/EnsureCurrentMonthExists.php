<?php

namespace App\Http\Middleware;

use App\Models\Month;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentMonthExists
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user) {
            return $next($request);
        }

        $exists = $user->months()
            ->where('year', now()->year)
            ->where('month_number', now()->month)
            ->exists();

        if (! $exists) {
            $activeMonth = $user->months()
                ->where('is_active', true)
                ->first();

            $income = $activeMonth ? $activeMonth->income : 0;
            $savings_goal = $activeMonth ? $activeMonth->savings_goal : 0;

            Month::forceCreate([
                'year' => now()->year,
                'month_number' => now()->month,
                'income' => $income,
                'savings_goal' => $savings_goal,
                'is_active' => true,
                'user_id' => $user->id,
            ]);

            if ($activeMonth) {
                $activeMonth->is_active = false;
                $activeMonth->save();
            }
        }

        return $next($request);
    }
}
