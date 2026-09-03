<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductKey extends Model
{
    protected $table = 'product_keys';

    protected $fillable = [
        'sku',
        'key_code',
        'order_id',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];
}
