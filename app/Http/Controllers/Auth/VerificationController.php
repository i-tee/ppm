<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;

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
