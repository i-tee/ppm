<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\SocialAuthController;

Route::get('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
Route::get('/auth/google/redirect', [SocialAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);
Route::get('/auth/yandex/redirect', [SocialAuthController::class, 'redirectToYandex']);
Route::get('/auth/yandex/callback', [SocialAuthController::class, 'handleYandexCallback']);
Route::post('/auth/social/authenticate', [SocialAuthController::class, 'authenticateFromSocial']);
Route::get('/social-auth-test', function () {
    if (session()->has('social_user')) {
        return view('social-auth-test');
    }
    return redirect('/')->with('error', 'Нет данных пользователя');
});
Route::get('/welcome', function () {
    return view('app');
})->name('welcome');
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^((?!welcome).)*$');