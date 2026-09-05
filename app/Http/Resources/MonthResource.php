<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonthResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $skippedAt = $this->skipped_at;

        return [
            'id' => $this->id,
            'year' => $this->year,
            'month_number' => $this->month_number,
            'income' => $this->income,
            'savings_goal' => $this->savings_goal,
            'is_active' => $this->is_active,
            'is_skipped' => $this->is_skipped,
            'skipped_at' => $skippedAt ? (Carbon::parse($skippedAt)->toIso8601String()) : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
