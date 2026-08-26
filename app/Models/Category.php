<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'name',
        'default_type',
    ];
    
    function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
