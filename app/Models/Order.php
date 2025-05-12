<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
      'user_id',
      'cart_id',
      'total',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
