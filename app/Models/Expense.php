<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'amount',
        'date',
        'type',
        'category_id',
    ];

    function category()
    {
        return $this->belongsTo(Category::class);
    }

    function month()
    {
        return $this->belongsTo(Month::class);
    }
}
