<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::all();
        return response()->json($products);
    }

    public function show(Product $product)
    {
        return response()->json($product);
    }

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

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['message' => 'Product deleted successfully.']);
    }



}
