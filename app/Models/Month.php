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

    protected $casts = [
        'is_active' => 'boolean',
        'is_skipped' => 'boolean',
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

    // Return a summary of the month (pure computation — shape decision belongs to MonthSummaryResource)
    public function summary(): array
    {
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
