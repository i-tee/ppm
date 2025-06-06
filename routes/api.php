<?php

use App\Http\Controllers\AuthController;
//use App\Http\Controllers\MailController;
use Illuminate\Support\Facades\Route;

//Route::get('/mail', [MailController::class, 'sendWelcomeEmail']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user', [AuthController::class, 'update']); // маршрут для обновления профиля
});
