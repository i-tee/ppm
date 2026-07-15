<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * S2S-клиент к основному бэкенду Avicenna (Фаза D, этап 3 — dual-write).
 *
 * Партнёрка минтит купон через `POST /api/v1/coupons/partner` (истина), а
 * старый INSERT в Joomla идёт следом (см. JoomlaCoupon::createCoupon). Все
 * ошибки маппятся в СТРОКОВЫЕ ключи, которые фронт матчит по значению
 * (как и локальные ошибки createCoupon) — важнее всего `coupon_code_exists`.
 *
 * Конфиг — `config/services.php → avicenna_backend` (base_url + source_token
 * + timeout). Заголовок `X-Source-Token` авторизует s2s-канал на бэке.
 */
class AvicennaBackendClient
{
    /**
     * Минт партнёрского купона на бэке.
     *
     * @param  array{mint_request_id:string,partner_ref:string,code:string,kind:string,value:int,commission_percent:int,valid_days?:int|null}  $payload
     * @return array{success:bool,error?:string,status?:int,data?:array,idempotent_hit?:bool}
     */
    public function mintPartnerCoupon(array $payload): array
    {
        $cfg = config('services.avicenna_backend');

        try {
            $resp = Http::withHeaders([
                'X-Source-Token' => (string) $cfg['source_token'],
                'Accept'         => 'application/json',
            ])
                ->timeout((int) ($cfg['timeout'] ?? 10))
                ->post(rtrim((string) $cfg['base_url'], '/') . '/api/v1/coupons/partner', $payload);
        } catch (\Throwable $e) {
            // Сеть недоступна/таймаут: купона в бэке МОГЛО не быть — ретрай с
            // тем же mint_request_id безопасен (идемпотентность бэка). В slice
            // 1–3 mint_request_id не персистится → ретрай на слое выше (slice 4–6,
            // таблица minted_coupons). Сейчас — сообщаем наверх сетевую ошибку.
            Log::error('avicenna_backend.mint_network_error', [
                'error' => $e->getMessage(),
                'code'  => $payload['code'] ?? null,
            ]);

            return ['success' => false, 'error' => 'coupon_backend_unreachable'];
        }

        // 200 (идемпотентный повтор) или 201 (создан).
        if ($resp->successful()) {
            return [
                'success'        => true,
                'data'           => (array) $resp->json('data'),
                'idempotent_hit' => (bool) $resp->json('idempotent_hit'),
                'status'         => $resp->status(),
            ];
        }

        // 422: занятый код (errors.code) → тот же ключ, что и локальная проверка
        // Joomla (`coupon_code_exists`). Иные 422 = рассинхрон валидаций ppm↔бэк.
        if ($resp->status() === 422) {
            if ($resp->json('errors.code') !== null) {
                return ['success' => false, 'error' => 'coupon_code_exists', 'status' => 422];
            }

            Log::warning('avicenna_backend.mint_validation_mismatch', ['body' => $resp->json()]);

            return ['success' => false, 'error' => 'coupon_backend_validation', 'status' => 422];
        }

        // 403: у источника нет capability (или токен невалиден) — миснастройка.
        if ($resp->status() === 403) {
            Log::error('avicenna_backend.mint_forbidden', ['body' => $resp->json()]);

            return ['success' => false, 'error' => 'coupon_backend_forbidden', 'status' => 403];
        }

        // Прочее (429/5xx/…).
        Log::error('avicenna_backend.mint_failed', [
            'status' => $resp->status(),
            'body'   => $resp->json(),
        ]);

        return ['success' => false, 'error' => 'coupon_backend_error', 'status' => $resp->status()];
    }
}
