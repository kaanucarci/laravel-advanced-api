<?php


use App\Http\Controllers\CartController;
use Illuminate\Support\Facades\Route;

//Requires Authentication
Route::group(['middleware' => ['auth:sanctum']], function() {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::get('/cart/items', [CartController::class, 'cart_items'])->name('cart.items.index');
    Route::put('/cart/update', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');
});
