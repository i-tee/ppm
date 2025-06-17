<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/{any}', function () {
    return view('app'); // app.blade.php
})->where('any', '.*');

Route::get('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
