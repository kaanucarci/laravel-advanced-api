<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
      'status',
      'user_id',
      'cart_id',
      'total',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function cartItems()
    {
        return $this->hasManyThrough(
            CartItem::class,
            Cart::class,
            'id',           // Cart tablosundaki `id` (foreign key)
            'cart_id',      // CartItem tablosundaki `cart_id`
            'cart_id',      // Order tablosundaki `cart_id`
            'id'            // Cart tablosundaki `id`
        );
    }
}
