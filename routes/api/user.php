<?php
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/user/register', [AuthController::class, 'register'])->name('user.register');
Route::post('/user/login', [AuthController::class, 'login'])->name('user.login');

//Requires Authentication
Route::group(['middleware' => ['auth:sanctum']], function() {
    Route::get('/user/me', [AuthController::class, 'me'])->name('user.me');
    Route::post('/user/logout', [AuthController::class, 'logout'])->name('user.logout');
});
