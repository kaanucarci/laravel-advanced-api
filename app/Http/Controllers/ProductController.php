<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{

    /**
     * @OA\Get(
     *     path="/products",
     *     tags={"Products"},
     *     summary="Get all products",
     *     @OA\Response(
     *         response=200,
     *         description="List of products",
     *     )
     * )
     */
    public function index()
    {
        $products = Product::all();
        return response()->json($products);
    }

    /**
     * @OA\Get(
     *     path="/products/{product}",
     *     tags={"Products"},
     *     summary="Get a product by ID",
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         required=true,
     *         description="Product ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product detail",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     )
     * )
     */
    public function show(Product $product)
    {
        return response()->json($product);
    }

    /**
     * @OA\Post(
     *     path="/products",
     *     tags={"Products"},
     *     summary="Create a new product",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "price"},
     *             @OA\Property(property="title", type="string", example="T-Shirt"),
     *             @OA\Property(property="description", type="string", example="Basic cotton t-shirt"),
     *             @OA\Property(property="price", type="number", format="float", example=79.99),
     *             @OA\Property(property="stock", type="integer", example=50)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product created successfully."),
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden, Only admins can perform this action"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
           'title' => 'required|string|max:255',
           'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $product = Product::create([
           'title' => $request->title,
           'slug' => Str::slug($request->title),
           'description' => $request->description ?? null,
           'price' => $request->price ?? null,
           'stock' => $request->stock ?? 0,
        ]);

        return response()->json([
            'message' => 'Product created successfully.',
            'data' => $product
        ]);
    }
    /**
     * @OA\Put(
     *     path="/products/{product}",
     *     tags={"Products"},
     *     summary="Update a product",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         required=true,
     *         description="Product ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "price"},
     *             @OA\Property(property="title", type="string", example="Updated T-Shirt"),
     *             @OA\Property(property="description", type="string", example="New version of cotton T-shirt"),
     *             @OA\Property(property="price", type="number", format="float", example=149.99),
     *             @OA\Property(property="stock", type="integer", example=30)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product updated successfully."),
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden, Only admins can perform this action"
     *     )
     * )
     */

    public function update(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
           'title' => 'required|string|max:255',
           'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $product->update($request->all());

        return response()->json([
            'message' => 'Product updated successfully.',
            'data' => $product
        ]);
    }
    /**
     * @OA\Delete(
     *     path="/products/{product}",
     *     tags={"Products"},
     *     summary="Delete a product",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         required=true,
     *         description="Product ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden, Only admins can perform this action"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     )
     * )
     */

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['message' => 'Product deleted successfully.']);
    }



}
