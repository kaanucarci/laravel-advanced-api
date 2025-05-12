<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

//Requires Authentication
Route::group(['middleware' => ['auth:sanctum']], function() {
    Route::get('/order', [OrderController::class, 'index'])->name('order.index');
    Route::post('/order', [OrderController::class, 'store'])->name('order.store');
});
