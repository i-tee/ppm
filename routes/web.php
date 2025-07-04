<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\SocialAuthController;

Route::get('/auth/google/redirect', [SocialAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);
Route::get('/auth/yandex/redirect', [SocialAuthController::class, 'redirectToYandex']);
Route::get('/auth/yandex/callback', [SocialAuthController::class, 'handleYandexCallback']);
Route::post('/auth/social/authenticate', [SocialAuthController::class, 'authenticateFromSocial']);

Route::get('/auth', function () {
    if (session()->has('social_user')) {
        return view('auth');
    }
    return redirect('/')->with('error', 'no data for user');
});

Route::get('/reset-password', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');

Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
