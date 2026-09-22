<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonthSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $skippedAt = $this->skipped_at;

        if ($this->is_skipped) {
            return [
                'is_skipped' => true,
                'skipped_at' => $skippedAt ? (Carbon::parse($skippedAt)->toIso8601String()) : null,
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
            'total_spent' => number_format((float) $this->totalSpent(), 2, '.', ''),
            'remaining' => number_format((float) $this->remaining(), 2, '.', ''),
            'available_to_spend' => number_format((float) $this->availableToSpend(), 2, '.', ''),
            'is_overspent' => $this->isOverSpent(),
            'spending_by_category' => $this->spendingByCategory()->map(function ($item) {
                return [
                    'category_name' => $item->category_name,
                    'total' => number_format((float) $item->total, 2, '.', ''),
                ];
            }),
        ];
    }
}
