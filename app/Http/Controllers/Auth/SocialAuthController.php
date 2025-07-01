<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Two\ProviderInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;
use App\Services\YandexSocialiteProvider;

class SocialAuthController extends Controller
{
    /**
     * Редирект на страницу авторизации Яндекса.
     */
    public function redirectToYandex(): RedirectResponse
    {
        $redirectUri = app()->environment('production')
            ? config('services.yandex.redirect')
            : config('services.yandex.redirect_dev');

        $provider = new YandexSocialiteProvider(
            request(),
            config('services.yandex.client_id'),
            config('services.yandex.client_secret'),
            $redirectUri
        );

        \Log::info('Yandex Redirect Params', [
            'client_id' => config('services.yandex.client_id'),
            'redirect_uri' => $redirectUri,
            'scopes' => $provider->getScopes(), // Используем геттер вместо прямого доступа
        ]);

        return $provider->redirect();
    }

    /**
     * Обработка коллбэка от Яндекса и сохранение данных пользователя.
     */
    public function handleYandexCallback(Request $request): RedirectResponse
    {
        $redirectUri = app()->environment('production')
            ? config('services.yandex.redirect')
            : config('services.yandex.redirect_dev');

        $provider = new YandexSocialiteProvider(
            $request,
            config('services.yandex.client_id'),
            config('services.yandex.client_secret'),
            $redirectUri
        );

        \Log::info('Yandex Callback Request', $request->all());

        try {
            $socialUser = $provider->user();
            $userData = [
                'provider' => 'yandex',
                'id' => $socialUser->getId(),
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'avatar' => $socialUser->getAvatar(),
            ];

            \Log::info('Yandex User Data', $userData);

            $request->session()->put('social_user', $userData);

            return redirect('/social-auth-test');
        } catch (\Exception $e) {

            dd($e->getMessage());

            \Log::error('Yandex Callback Error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect('/')->with('error', 'Ошибка авторизации через Яндекс: ' . $e->getMessage());
        }
    }

    // Оставь методы для Google как есть
    public function redirectToGoogle(): RedirectResponse
    {
        return \Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            $socialUser = \Socialite::driver('google')->user();
            $userData = [
                'provider' => 'google',
                'id' => $socialUser->getId(),
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'avatar' => $socialUser->getAvatar(),
            ];
            Log::channel('socialite')->info('Google User Data', $userData);
            $request->session()->put('social_user', $userData);
            return redirect('/social-auth-test');
        } catch (\Exception $e) {
            Log::channel('socialite')->error('Google Auth Error', ['error' => $e->getMessage()]);
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

        $user = User::firstOrCreate(
            ['email' => $socialUser['email']],
            [
                'name' => $socialUser['name'],
                'password' => Hash::make(\Str::random(16)),
            ]
        );

        if (!$user->email_verified_at) {
            $user->email_verified_at = Carbon::now();
            $user->save();
        }

        $token = $user->createToken('spa-token')->plainTextToken;

        return redirect()->to('/welcome?token=' . urlencode($token) . '&email=' . urlencode($socialUser['email']) . '&email_verified=1');
    }
}
