<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Month extends Model
{
    protected $fillable = [
        'savings_goal',
        'income'
    ];

    function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
