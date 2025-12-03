<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\JoomlaCoupon;
use App\Models\PartnerApplication;
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

        // \Log::info('Yandex Redirect Params', [
        //     'client_id' => config('services.yandex.client_id'),
        //     'redirect_uri' => $redirectUri,
        //     'scopes' => $provider->getScopes(),
        // ]);

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

        // \Log::info('Yandex Callback Request', $request->all());

        try {
            $socialUser = $provider->user();
            $userData = [
                'provider' => 'yandex',
                'id' => $socialUser->getId(),
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'avatar' => $socialUser->getAvatar(),
            ];

            // \Log::info('Yandex User Data', $userData);

            $request->session()->put('social_user', $userData);

            return redirect('/auth');
        } catch (\Exception $e) {

            //dd($e->getMessage());

            // \Log::error('Yandex Callback Error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect('/')->with('error', 'Error during Yandex authentication: ' . $e->getMessage());
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
            // Log::channel('socialite')->info('Google User Data', $userData);
            $request->session()->put('social_user', $userData);
            return redirect('/auth');
        } catch (\Exception $e) {
            // Log::channel('socialite')->error('Google Auth Error', ['error' => $e->getMessage()]);
            return redirect('/')->with('error', 'Error during Google authentication: ' . $e->getMessage());
        }
    }

    /**
     * Авторизация или регистрация пользователя из социальных данных.
     */
    public function authenticateFromSocial(Request $request): RedirectResponse
    {
        $socialUser = $request->session()->get('social_user');

        if (!$socialUser) {
            return redirect('/')->with('error', 'No data for authentication');
        }

        // Ищем или создаём пользователя
        $user = User::where('email', $socialUser['email'])->first();

        if ($user) {
            // Пользователь существует, обновляем аватар, если его нет
            if (!$user->avatar && !empty($socialUser['avatar'])) {
                $user->avatar = $socialUser['avatar'];
                $user->save();
            }
        } else {
            // Создаём нового пользователя с аватаром
            $user = User::create([
                'name' => $socialUser['name'],
                'email' => $socialUser['email'],
                'password' => Hash::make(\Str::random(16)),
                'avatar' => $socialUser['avatar'] ?? null,
            ]);

            // Тут надо добавитть новый метод на проверку, существует ли уже этот юзер в Joomla и является ли он партнером
            // Проверяем, является ли новый пользователь партнёром в Joomla
            $isJoomlaPartner = JoomlaCoupon::isJoomlaPartner($socialUser['email']);
            Log::info("[SocialAuth] Новый пользователь {$socialUser['email']} является Joomla-партнёром: " . ($isJoomlaPartner ? 'да' : 'нет'));

            if ($isJoomlaPartner) {
                // Если пользователь — партнёр из Joomla, создаём автоматическую заявку
                if ($isJoomlaPartner) {
                    $application = PartnerApplication::createApprovedDefaultPartnerApplication($user);
                    Log::info("[SocialAuth] Автоматическая заявка создана для партнёра ID {$user->id}, заявка ID {$application->id}");
                }
            }
        }

        // Отмечаем email как верифицированный, если ещё не сделано
        if (!$user->email_verified_at) {
            $user->email_verified_at = Carbon::now();
            $user->save();
        }

        // Генерируем токен
        $token = $user->createToken('spa-token')->plainTextToken;

        // Перенаправляем на страницу приветствия
        return redirect()->to('/welcome?token=' . urlencode($token) . '&email=' . urlencode($socialUser['email']) . '&email_verified=1');
    }
}
