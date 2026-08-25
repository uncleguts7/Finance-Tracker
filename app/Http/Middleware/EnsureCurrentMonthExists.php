<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Month;

class EnsureCurrentMonthExists
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $exists = Month::where('year', now()->year)
                        ->where('month_number', now()->month)
                        ->exists();

        if(!$exists)
        {
        $ActiveMonth = Month::where('is_active', true)->first();

        $income = $ActiveMonth ? $ActiveMonth->income : 0;
        $savings_goal = $ActiveMonth ? $ActiveMonth->savings_goal : 0;

        Month::forceCreate([
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
                
        }
        
        return $next($request);
    }
}
