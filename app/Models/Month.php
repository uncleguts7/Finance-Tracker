<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Month extends Model
{
    protected $fillable = [
        'savings_goal',
        'income',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    // Calculate the total amount spent in this month
    public function totalSpent()
    {
        return $this->expenses()->sum('amount');
    }

    // Calculate the remaining amount after expenses
    public function remaining()
    {
        return $this->income - $this->totalSpent();
    }

    // Calculate the available amount to spend after considering the savings goal
    public function availableToSpend()
    {
        return $this->income - $this->savings_goal;
    }

    // Calculate if the month is overspent or not
    public function isOverSpent()
    {
        return $this->totalSpent() > $this->availableToSpend();
    }

    // Calculate the spending by category for this month
    public function spendingByCategory()
    {
        return $this->expenses()
            ->join('categories', 'expenses.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as category_name, SUM(expenses.amount) as total')
            ->groupBy('categories.name')
            ->get();
    }

    // Return a summary of the month
    public function summary()
    {
        if ($this->is_skipped) {
            return [
                'is_skipped' => true,
                'skipped_at' => $this->skipped_at,
                'income' => null,
                'savings_goal' => null,
                'total_spent' => null,
                'remaining' => null,
                'available_to_spend' => null,
                'is_overspent' => null,
                'spending_by_category' => [],
            ];
        }

        return [
            'income' => $this->income,
            'savings_goal' => $this->savings_goal,
            'total_spent' => $this->totalSpent(),
            'remaining' => $this->remaining(),
            'available_to_spend' => $this->availableToSpend(),
            'is_overspent' => $this->isOverSpent(),
            'spending_by_category' => $this->spendingByCategory(),
        ];
    }
}
