<?php

use App\Http\Controllers\AuthController;
//use App\Http\Controllers\MailController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\PartnersSettingController;
use App\Http\Controllers\PartnerApplicationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserCouponController;
use App\Http\Controllers\RequisiteController;
use App\Http\Controllers\RequisitesSettingController;
use App\Http\Controllers\PayoutRequestController;
use App\Http\Controllers\ImpersonateController;
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

    // Отладочные роуты, открыты любому залогиненному партнёру — известная
    // дыра, отложена до этапа 1.0 (docs/operations.md §5), в 1.3 не трогаем.
    Route::get('/dev2', [UserCouponController::class, 'ddv']);
    Route::get('/dev3', [UserCouponController::class, 'data']);
    Route::get('/dev4', [UserCouponController::class, 'index']);

    // Любое уведомление любому пользователю от любого партнёра — известная
    // дыра, отложена до этапа 1.0, в 1.3 не трогаем.
    Route::post('/notifications/send', [NotificationController::class, 'send']);

    // ── Общие роуты: доступны всем залогиненным, включая сотрудников ──
    Route::post('/user/avatar', [AuthController::class, 'uploadAvatar']);
    Route::put('/user/change-password', [AuthController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user', [AuthController::class, 'update']);
    Route::post('/email/resend', [VerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.resend');
    Route::get('/ps', [PartnersSettingController::class, 'index']);
    Route::get('/rs', [RequisitesSettingController::class, 'index']);

    // ── Реквизиты: список на проверку, одобрение и удаление – общие роуты
    // (партнёр удаляет свои, админ/бухгалтер – любые), роль проверяется
    // внутри RequisiteController через canManageFinance(). Свои реквизиты
    // (список/создание) – в группе `partner` ниже. ──
    Route::get('/user/requisites-all', [RequisiteController::class, 'all']);
    Route::put('/user/requisites/{id}/verify', [RequisiteController::class, 'verify']);
    Route::delete('/user/requisites/{id}', [RequisiteController::class, 'destroy']);

    // ── Финансовые админ-роуты: `finance` — админ и бухгалтер одинаково ──
    Route::middleware('finance')->group(function () {
        Route::get('/admin/payout-requests-prepared', [PayoutRequestController::class, 'adminIndexPrepared']);
        Route::get('/admin/payout-requests', [PayoutRequestController::class, 'adminIndex']);
        Route::post('/admin/payout-ticked-reminder/{id}', [PayoutRequestController::class, 'adminTicketReminder']);
        Route::get('/admin/payout-requests/{id}', [PayoutRequestController::class, 'adminShow']);
        Route::put('/admin/payout-requests/{id}/{status}', [PayoutRequestController::class, 'adminStatusUpdate']);
        Route::put('/admin/payout-requests-ticket-abort/{id}', [PayoutRequestController::class, 'adminTickedAbort']);
        Route::put('/admin/payout-requests-received/{id}', [PayoutRequestController::class, 'adminReceived']);
    });

    // ── Админ-роуты: пользователи, impersonate, заявки партнёров ──
    // DELETE .../{id} ведёт на несуществующий adminDestroy — оставлено как
    // есть (не в объёме 1.3), но остаётся под `admin`, не `finance`.
    Route::middleware('admin')->group(function () {
        Route::delete('/admin/payout-requests/{id}', [PayoutRequestController::class, 'adminDestroy']);

        Route::get('/admin/users', [ImpersonateController::class, 'index']);
        Route::post('/admin/impersonate/{user}', [ImpersonateController::class, 'impersonate']);
        Route::post('/admin/impersonate/stop', [ImpersonateController::class, 'stop']);

        // Закрытие дыры (этап 1.3): раньше висело на auth:sanctum — любой
        // партнёр читал все заявки (с телефонами и почтами), мог одобрить
        // сам себя и удалить чужие.
        Route::get('/partner-applications', [PartnerApplicationController::class, 'index']);
        Route::get('/partner-applications/statuses', [PartnerApplicationController::class, 'getStatuses']);
        Route::get('/partner-applications/{id}', [PartnerApplicationController::class, 'show']);
        Route::put('/partner-applications/{id}', [PartnerApplicationController::class, 'update']);
        Route::delete('/partner-applications/{id}', [PartnerApplicationController::class, 'destroy']);
    });

    // ── Партнёрские роуты: сотрудник (1|2|3) не может быть партнёром ──
    Route::middleware('partner')->group(function () {
        Route::post('/partner-applications', [PartnerApplicationController::class, 'store']);

        Route::post('/payout-requests', [PayoutRequestController::class, 'store']);
        Route::post('/payout-requests/{id}/ticket', [PayoutRequestController::class, 'uploadTicket']);

        Route::get('/user/requisites', [RequisiteController::class, 'index']);
        Route::post('/user/requisites', [RequisiteController::class, 'store']);

        Route::get('/user/coupons', [UserCouponController::class, 'index']);
        Route::post('/user/check-promocode', [UserCouponController::class, 'check']);
        Route::get('/user/business-data', [UserCouponController::class, 'data']);
        Route::post('/user/coupon/create', [UserCouponController::class, 'create']);
        Route::post('/user/coupon/orders', [UserCouponController::class, 'getOrderInfoByCouponId']);
        Route::post('/user/coupons/hide', [UserCouponController::class, 'hideCoupon']);
        Route::post('/user/coupons/restore', [UserCouponController::class, 'restoreCoupon']);
    });
});

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
