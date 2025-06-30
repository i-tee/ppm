<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;

class SocialAuthController extends Controller
{
    /**
     * Редирект на страницу авторизации Google.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Обработка коллбэка от Google и сохранение данных пользователя.
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver('google')->user();

            $userData = [
                'provider' => 'google',
                'id' => $socialUser->getId(),
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'avatar' => $socialUser->getAvatar(),
            ];

            Log::channel('socialite')->info('Social User Data', $userData);

            $request->session()->put('social_user', $userData);

            return redirect('/social-auth-test');
        } catch (\Exception $e) {
            Log::channel('socialite')->error('Social Auth Error', ['error' => $e->getMessage(), 'provider' => 'google']);
            return redirect('/')->with('error', 'Ошибка авторизации через Google');
        }
    }

    /**
     * Авторизация или регистрация пользователя из социальных данных.
     */
    public function authenticateFromSocial(Request $request): RedirectResponse
    {
        $socialUser = $request->session()->get('social_user');

        if (!$socialUser) {
            return redirect('/')->with('error', 'Нет данных для авторизации');
        }

        // Проверяем или создаем пользователя с подтвержденным email
        $user = User::firstOrCreate(
            ['email' => $socialUser['email']],
            [
                'name' => $socialUser['name'],
                'password' => Hash::make(\Str::random(16)), // Генерируем случайный пароль
            ]
        );

        // Устанавливаем email_verified_at, если еще не подтвержден
        if (!$user->email_verified_at) {
            $user->email_verified_at = Carbon::now();
            $user->save();
        }

        // Создаем токен Sanctum
        $token = $user->createToken('spa-token')->plainTextToken;

        // Передаем токен и данные пользователя в редирект
        return redirect()->to('/welcome?token=' . urlencode($token) . '&email=' . urlencode($socialUser['email']) . '&email_verified=1');
    }
}