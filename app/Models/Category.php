<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'default_type',
    ];
    
    function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
