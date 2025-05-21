<?php

use App\Http\Controllers\OrderController;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\Facades\Route;

//Requires Authentication
Route::group(['middleware' => ['auth:sanctum']], function() {
    Route::get('/order', [OrderController::class, 'index'])->name('order.index');
    Route::get('/order/{order}', [OrderController::class, 'show'])->name('order.show');
    Route::post('/order', [OrderController::class, 'store'])->name('order.store');
    Route::patch('/order/{order}/cancel', [OrderController::class, 'cancel'])->name('order.cancel');
});

//ADMIN
Route::group(['middleware' => ['auth:sanctum', RoleMiddleware::class]], function() {
    Route::patch('/order/{order}', [OrderController::class, 'update'])->name('order.update');
});

