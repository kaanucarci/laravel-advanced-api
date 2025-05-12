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
     * @OA\Get(
     *     path="/cart",
     *     tags={"Cart"},
     *     summary="Get cart info",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Returns the cart",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function index()
    {

        $cart = $this->get_cart();
        return response()->json(['data' => $cart]);
    }

    /**
     * @OA\Get(
     *     path="/cart/items",
     *     tags={"Cart"},
     *     summary="Get items in the cart",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of cart items",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="product_id", type="integer"),
     *                     @OA\Property(property="quantity", type="integer"),
     *                     @OA\Property(
     *                         property="product",
     *                         type="object",
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="price", type="number", format="float"),
     *                         @OA\Property(property="stock", type="integer")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function cart_items()
    {
        $cartItems = $this->get_cart_items();
        $totalPrice = $this->cart_total($cartItems);
        return response()->json(['data' => $cartItems, 'totalPrice' => $totalPrice]);
    }

    /**
     * @OA\Put(
     *     path="/cart/update",
     *     tags={"Cart"},
     *     summary="Add or update product in cart",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id", "quantity"},
     *             @OA\Property(property="product_id", type="integer", example=1),
     *             @OA\Property(property="quantity", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cart updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Card updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="product_id", type="integer"),
     *                     @OA\Property(property="quantity", type="integer"),
     *                     @OA\Property(
     *                         property="product",
     *                         type="object",
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="price", type="number", format="float")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input or stock limit exceeded",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
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
                if ($request->quantity < 0 && ($request->quantity * -1) > $cartItem->quantity)
                    return response()->json(['message' => 'You cannot reduce more than the quantity in your cart!'], 400);

                $cartItem->quantity += $request->quantity;

                if ($cartItem->quantity == 0)
                    $cartItem->delete();
                else
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

        $totalPrice = $this->cart_total($cartItems);

        return response()->json([
            'message' => 'Card updated successfully',
            'data' => $cartItems,
            'totalPrice' => $totalPrice,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/cart",
     *     tags={"Cart"},
     *     summary="Clear all items from the authenticated user's cart",
     *     description="Deletes all items from the active cart of the currently authenticated user.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Cart cleared successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cart items have been cleared")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function clear()
    {
        $cart = $this->get_cart();

        CartItem::where('cart_id', $cart->id)
            ->delete();

        //Delete cache
        Cache::forget($this->cache_key);

        return response()->json(['message' => 'Cart items have been cleared']);
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

    public function get_cart_items()
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

        return $cartItems;
    }

    public function cart_total($cartItems)
    {
        $totalPrice = 0;

        foreach ($cartItems as $cartItem) {
            $totalPrice += ((float)$cartItem->product->price * (int)$cartItem->quantity);
        }

        return $totalPrice;
    }

}
