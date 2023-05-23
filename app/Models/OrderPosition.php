<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'position_id', 'count'
    ];

    protected $table = 'order_position';
}
