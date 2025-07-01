<?php

namespace App\Services;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class YandexSocialiteProvider extends AbstractProvider implements ProviderInterface
{
    protected $scopes = []; // Пустой массив вместо ['openid', 'email', 'profile']

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://oauth.yandex.ru/authorize', $state);
    }

    protected function getTokenUrl()
    {
        return 'https://oauth.yandex.ru/token';
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://login.yandex.ru/info', [
            'headers' => [
                'Authorization' => 'OAuth ' . $token,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['id'],
            'nickname' => $user['login'],
            'name' => $user['real_name'] ?? $user['login'],
            'email' => $user['default_email'] ?? null,
            'avatar' => $user['default_avatar_id'] ? 'https://avatars.yandex.net/get-yandex/' . $user['default_avatar_id'] : null,
        ]);
    }

    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'form_params' => $this->getTokenFields($code),
        ]);
        \Log::info('Yandex Token Response', ['body' => $response->getBody()->getContents()]);
        return json_decode($response->getBody(), true);
    }

    public function getScopes()
    {
        return $this->scopes;
    }
}