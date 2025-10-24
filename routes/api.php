<?php

use App\Http\Controllers\AuthController;
//use App\Http\Controllers\MailController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\PartnersSettingController;
use App\Http\Controllers\PartnerApplicationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserCouponController;
use App\Http\Controllers\RequisiteController;
use App\Http\Controllers\PayoutRequestController;
use App\Http\Controllers\DevController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

//Route::get('/mail', [MailController::class, 'sendWelcomeEmail']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/dev2', [UserCouponController::class, 'ddv']);
    Route::get('/dev3', [UserCouponController::class, 'data']);
    Route::get('/dev4', [UserCouponController::class, 'index']);

    Route::post('/payout-requests', [PayoutRequestController::class, 'store']);

    Route::get('/user/requisites', [RequisiteController::class, 'index']);
    Route::post('/user/requisites', [RequisiteController::class, 'store']);
    Route::delete('/user/requisites/{id}', [RequisiteController::class, 'destroy']);

    Route::get('/user/coupons', [UserCouponController::class, 'index']);
    Route::post('/user/check-promocode', [UserCouponController::class, 'check']);
    Route::get('/user/business-data', [UserCouponController::class, 'data']);
    Route::post('/user/coupon/create', [UserCouponController::class, 'create']);
    Route::post('/user/coupon/orders', [UserCouponController::class, 'getOrderInfoByCouponId']);

    Route::put('/user/change-password', [AuthController::class, 'changePassword']);

    Route::post('/partner-applications', [PartnerApplicationController::class, 'store']);
    Route::get('/partner-applications', [PartnerApplicationController::class, 'index']);
    Route::get('/partner-applications/{id}', [PartnerApplicationController::class, 'show']);
    Route::put('/partner-applications/{id}', [PartnerApplicationController::class, 'update']);
    Route::delete('/partner-applications/{id}', [PartnerApplicationController::class, 'destroy']);

    Route::post('/user/avatar', [AuthController::class, 'uploadAvatar']);

    Route::get('/ps', [PartnersSettingController::class, 'index']);

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user', [AuthController::class, 'update']);

    Route::post('/email/resend', [VerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.resend');

    Route::post('/notifications/send', [NotificationController::class, 'send']);
});

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
