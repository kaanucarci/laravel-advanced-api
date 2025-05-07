<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    protected $user_id;
    protected $cache_key;

    public function __construct()
    {
        $this->user_id = auth()->id();
        $this->cache_key = 'cart_items_user_:' . $this->user_id;
    }

    /**
     * Display a listing of the cart.
     */
    public function index()
    {

        $cart = $this->get_cart();
        return response()->json(['data' => $cart]);
    }

    public function cart_items()
    {
        $cacheKey = $this->cache_key;

        if (Cache::has($cacheKey))
        {
           $cartItems = Cache::get($cacheKey);
        }
        else{
            $cart = $this->get_cart();

            $cartItems = CartItem::with('product')
                ->where('cart_id', $cart->id)
                ->get();

            Cache::put($cacheKey, $cartItems, now()->addMinutes(10));
        }



        return response()->json(['data' => $cartItems]);
    }

    /**
     * Update the specified cart in storage.
     */
    public function update(Request $request)
    {
        $cacheKey = $this->cache_key;

        //Validate
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer',
        ]);

        if ($validator->fails())
            return response()->json(['message' => $validator->errors()->first(), 400]);
        //Validate Ends


        $cart = $this->get_cart();

        //Checking if product added to cart before
        $cartItem = CartItem::with('product')
            ->where('cart_id', $cart->id)
            ->where('product_id', $request->product_id)
            ->first();

        //If the product has been added before, just update the quantity but only if the stock is enough
        if ($cartItem)
        {
            if ($cartItem->product->stock > $cartItem->quantity + $request->quantity)
            {
                $cartItem->quantity += $request->quantity;
                $cartItem->save();
            }
            else
                return response()->json(['message' => 'Stock limit exceeded'], 400);
        }
        //If not add product to cart
        else {
            if ($request->quantity <= 0)
                return response()->json(['message' => 'Product never added before, quantity must be greater than zero!'], 400);

            $product = Product::find($request->product_id);
            if ($product->stock >= $request->quantity)
            {
                CartItem::create([
                    'cart_id' => $cart->id,
                    'quantity' => $request->quantity,
                    'product_id' => $request->product_id,
                ]);
            }
            else
                return response()->json(['message' => 'Stock limit exceeded'], 400);
        }

        $cartItems = CartItem::with('product')
            ->where('cart_id', $cart->id)
            ->get();

        //Delete cache
        Cache::forget($cacheKey);

        return response()->json([
            'message' => 'Card updated successfully',
            'data' => $cartItems
        ]);
    }

    private function get_cart()
    {
        return Cart::where('user_id', $this->user_id)
            ->where('status', 'active')
            ->latest()
            ->firstOrCreate([
                'user_id' => $this->user_id,
            ]);
    }

}
