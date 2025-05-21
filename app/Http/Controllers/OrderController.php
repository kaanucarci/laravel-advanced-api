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
     *     summary="Get a list of orders",
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
        $orders = Order::with('cartItems.product')
            ->orderByDesc('created_at')
            ->when(!auth()->user()->isAdmin(), function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->get();

        return response()->json([
            'data' => $orders,
            'message' => $orders->isEmpty() ? 'No orders have been placed yet.' : 'Orders retrieved successfully.',
        ]);
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
     * @OA\Get(
     *     path="/order/{order}",
     *     tags={"Orders"},
     *     summary="Get a specific order by ID",
     *     description="Returns a single order with its cart items and related product details.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         required=true,
     *         description="ID of the order to retrieve",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order found successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=2),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="cart_id", type="integer", example=3),
     *                 @OA\Property(property="total", type="number", format="float", example=149.99),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-12T21:00:00Z"),
     *                 @OA\Property(property="cart_items", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="product_id", type="integer", example=5),
     *                         @OA\Property(property="quantity", type="integer", example=2),
     *                         @OA\Property(property="product", type="object",
     *                             @OA\Property(property="id", type="integer", example=5),
     *                             @OA\Property(property="title", type="string", example="Bluetooth Kulaklık"),
     *                             @OA\Property(property="price", type="number", format="float", example=299.99)
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */

    public function show(Order $order)
    {
        $order->load('cartItems.product');

        return response()->json(['data' => $order]);
    }



    /**
     * @OA\Patch(
     *     path="/order/{order}",
     *     tags={"Orders"},
     *     summary="Admin: Update the status of an order",
     *     description="Allows an admin to update the status of a specific order. Requires authentication and admin role.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         required=true,
     *         description="ID of the order to update",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 description="New status of the order",
     *                 enum={"pending", "processing", "cancelled", "completed"},
     *                 example="processing"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Order updated successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The status field is required.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden – Only admins can perform this action"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Update failed"
     *     )
     * )
     */



    public function update(Order $order, Request $request)
    {
        $validator = Validator::make($request->all(), [
           'status' => 'required|in:pending,processing,cancelled,completed',
        ]);
        if ($validator->fails()) return response()->json(['message' => $validator->errors()->first()], 400);

        $stmt = $order->update([
           'status' => $request->status,
        ]);

        return response()->json(['message' => $stmt ? 'Order updated successfully.' : 'Error updating order.'] , $stmt ? 200 : 500);
    }

    /**
     * @OA\Patch(
     *     path="/order/{order}/cancel",
     *     tags={"Orders"},
     *     summary="Cancel a specific order",
     *     description="Marks a given order as cancelled. Only authenticated users can cancel their orders.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         required=true,
     *         description="ID of the order to cancel",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order cancelled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Order cancelled successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */

    public function cancel(Order $order)
    {
        $order->status = 'cancelled';
        $order->save();

        return response()->json(['message' => 'Order cancelled successfully.']);
    }
}
