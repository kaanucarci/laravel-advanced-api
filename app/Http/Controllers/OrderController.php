<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * @OA\Get(
     *     path="/order",
     *     tags={"Orders"},
     *     summary="Get all orders for authenticated user",
     *     description="Returns a list of orders with related product information. Requires authentication.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response. May return empty list.",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(
     *                         property="data",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="user_id", type="integer", example=5),
     *                             @OA\Property(property="product_id", type="integer", example=2),
     *                             @OA\Property(property="quantity", type="integer", example=3),
     *                             @OA\Property(property="product", type="object", example={
     *                                 "id": 2,
     *                                 "title": "Wireless Mouse",
     *                                 "price": 59.99
     *                             })
     *                         )
     *                     )
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="message", type="string", example="No orders have been placed yet."),
     *                     @OA\Property(property="data", type="null")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */


    public function index()
    {
        $orders = Order::with('product')->orderBy('created_at', 'desc')->get();

        if ($orders->isEmpty())
            return response()->json(['message' => 'No orders have been placed yet.', 'data' => null] );

        return response()->json(['data' => $orders]);
    }

    /**
     * @OA\Post(
     *     path="/order",
     *     tags={"Orders"},
     *     summary="Place a new order",
     *     description="Creates a new order for the authenticated user.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Order placed successfully."),
     *             @OA\Property(property="order", type="object", example={
     *                 "id": 10,
     *                 "user_id": 1,
     *                 "cart_id": 3,
     *                 "status": "pending",
     *                 "created_at": "2025-05-06T12:34:56.000000Z"
     *             })
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error or empty cart",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cart is empty.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */

    public function store(Request $request)
    {
       $cart_controller = new CartController();
       $cart_items = $cart_controller->get_cart_items();
       if($cart_items->isEmpty())
           return response()->json(['message' => 'No cart items have been placed yet.', 'data' => null]);

      $order = Order::create([
          'cart_id' =>  $cart_items[0]->cart_id,
          'user_id' =>  auth()->id(),
          'total' => $cart_controller->cart_total($cart_items),
       ]);

       return response()->json(['message' => 'Order created successfully.', 'data' => $order]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
