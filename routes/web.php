<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

use App\Http\Controllers\Auth\SocialAuthController;

Route::get('/auth/google/redirect', [SocialAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);
Route::get('/social-auth-test', function () {
    if (session()->has('social_user')) {
        return view('social-auth-test');
    }
    return redirect('/')->with('error', 'Нет данных пользователя');
});

Route::get('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');

Route::get('/{any}', function () {
    return view('app'); // app.blade.php
})->where('any', '.*');
