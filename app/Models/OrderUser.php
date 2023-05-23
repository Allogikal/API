<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'user_id'
    ];

    protected $table = 'order_user';
}
