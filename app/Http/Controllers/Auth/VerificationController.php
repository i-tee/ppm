<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PartnerApplication;
use App\Models\JoomlaCoupon;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Log;

class VerificationController extends Controller
{
    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        $data = null;

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            $data = response()->json(['message' => 'Invalid verification link'], 400);
        }

        if ($user->hasVerifiedEmail()) {
            $data = response()->json(['message' => 'Email already verified'], 400);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // Отправка уведомления об успешной верификации
        $user->sendVerificationCompleteNotification();

        // Обновляем пользователя и возвращаем успешный ответ с редиректом
        $user->refresh();

        // Проверяем, есть ли этот юзер в джумла, и партнер ли он уже
        $isJoomlaPartner = JoomlaCoupon::isJoomlaPartner($user['email']);
        Log::info("[ManualAuth] Новый пользователь {$user['email']} является Joomla-партнёром: " . ($user ? 'да' : 'нет'));

        if ($isJoomlaPartner) {
            // Если пользователь — партнёр из Joomla, создаём автоматическую заявку
            if ($isJoomlaPartner) {
                $application = PartnerApplication::createApprovedDefaultPartnerApplication($user);
                Log::info("[ManualAuth] Автоматическая заявка создана для партнёра ID {$user->id}, заявка ID {$application->id}");
            }
        }

        return response()->json([
            'data' => $data,
            'user' => $user,
        ], 303)->header('Location', '/dashboard/types');
    }

    public function resend(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 400);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email sent'], 200);
    }
}
