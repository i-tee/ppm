<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;

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

            // Преобразуем данные пользователя в массив
            $userData = [
                'provider' => 'google',
                'id' => $socialUser->getId(),
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'avatar' => $socialUser->getAvatar(),
            ];

            // Логируем данные для отладки
            Log::channel('socialite')->info('Social User Data', $userData);

            // Сохраняем данные в сессию с указанием провайдера
            $request->session()->put('social_user', $userData);

            // Редирект на общую тестовую страницу
            return redirect('/social-auth-test');
            
        } catch (\Exception $e) {
            Log::channel('socialite')->error('Social Auth Error', ['error' => $e->getMessage(), 'provider' => 'google']);
            return redirect('/')->with('error', 'Ошибка авторизации через Google');
        }
    }
}