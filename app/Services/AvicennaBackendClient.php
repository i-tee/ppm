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
    /** Сколько партнёров в одном раунде Http::pool (×2 эндпоинта = 10 запросов). */
    private const POOL_PARTNERS = 5;

    /** Предел страниц на один эндпоинт — тот же, что у одиночных методов. */
    private const MAX_PAGES = 50;

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
            // Сеть недоступна/таймаут: купон в бэке мог как создаться, так и
            // нет — ретрай с тем же mint_request_id безопасен (идемпотентность
            // бэка). mint_request_id уже сохранён в журнале minted_coupons до
            // вызова (JoomlaCoupon::createCoupon), и при сетевой ошибке строка
            // журнала не удаляется → повтор партнёром с тем же кодом
            // переиспользует id. Здесь — просто сообщаем наверх сетевую ошибку.
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

    /**
     * Начисления партнёра из леджера нового сайта (Фаза D, слайс B).
     *
     * Тянет ВСЕ строки (пройдя по страницам) — баланс и списки в ЛК должны
     * учитывать всё, не только первую страницу. `totals` берём с первой
     * страницы (они считаются по всей выборке на бэке). Best-effort: при
     * сбое возвращаем success=false, вызывающий деградирует к Joomla-данным.
     *
     * @return array{success:bool,rows:array,totals:array|null}
     */
    public function getAccruals(string $partnerRef, ?string $couponCode = null): array
    {
        $cfg    = config('services.avicenna_backend');
        $rows   = [];
        $totals = null;
        $page   = 1;

        do {
            try {
                $resp = Http::withHeaders([
                    'X-Source-Token' => (string) $cfg['source_token'],
                    'Accept'         => 'application/json',
                ])
                    ->timeout((int) ($cfg['timeout'] ?? 10))
                    ->get(rtrim((string) $cfg['base_url'], '/') . '/api/v1/partner/accruals', array_filter([
                        'partner_ref' => $partnerRef,
                        'coupon_code' => $couponCode,
                        'per_page'    => 100,
                        'page'        => $page,
                    ], fn ($v) => $v !== null && $v !== ''));
            } catch (\Throwable $e) {
                Log::error('avicenna_backend.accruals_network_error', [
                    'error' => $e->getMessage(), 'partner_ref' => $partnerRef,
                ]);

                return ['success' => false, 'rows' => [], 'totals' => null];
            }

            if (! $resp->successful()) {
                Log::error('avicenna_backend.accruals_failed', [
                    'status' => $resp->status(), 'partner_ref' => $partnerRef,
                ]);

                return ['success' => false, 'rows' => [], 'totals' => null];
            }

            $body   = $resp->json();
            $totals ??= $body['totals'] ?? null;
            foreach (($body['data'] ?? []) as $r) {
                $rows[] = $r;
            }

            $lastPage = (int) ($body['meta']['last_page'] ?? 1);
            $page++;
        } while ($page <= $lastPage && $page <= 50); // guard: не более 50 страниц (5000 строк)

        return ['success' => true, 'rows' => $rows, 'totals' => $totals];
    }

    /**
     * Погашения БОНУСНЫХ купонов партнёра с бэка (Фаза D, этап 4 — follow-up).
     *
     * Парный к getAccruals канал: у бонусника комиссии нет → в леджере
     * начислений его нет, и через getAccruals он не виден. Этот эндпоинт отдаёт
     * оплаченные заказы по бонусным купонам — чтобы ЛК показал «использован» +
     * заказ, включая погашение на новом сайте. Денег нет (totals отсутствуют),
     * только факт использования. Тянет ВСЕ страницы; best-effort — при сбое
     * success=false, вызывающий деградирует к чистым Joomla-данным.
     *
     * @return array{success:bool,rows:array}
     */
    public function getRedemptions(string $partnerRef, ?string $couponCode = null): array
    {
        $cfg  = config('services.avicenna_backend');
        $rows = [];
        $page = 1;

        do {
            try {
                $resp = Http::withHeaders([
                    'X-Source-Token' => (string) $cfg['source_token'],
                    'Accept'         => 'application/json',
                ])
                    ->timeout((int) ($cfg['timeout'] ?? 10))
                    ->get(rtrim((string) $cfg['base_url'], '/') . '/api/v1/partner/redemptions', array_filter([
                        'partner_ref' => $partnerRef,
                        'coupon_code' => $couponCode,
                        'per_page'    => 100,
                        'page'        => $page,
                    ], fn ($v) => $v !== null && $v !== ''));
            } catch (\Throwable $e) {
                Log::error('avicenna_backend.redemptions_network_error', [
                    'error' => $e->getMessage(), 'partner_ref' => $partnerRef,
                ]);

                return ['success' => false, 'rows' => []];
            }

            if (! $resp->successful()) {
                Log::error('avicenna_backend.redemptions_failed', [
                    'status' => $resp->status(), 'partner_ref' => $partnerRef,
                ]);

                return ['success' => false, 'rows' => []];
            }

            $body = $resp->json();
            foreach (($body['data'] ?? []) as $r) {
                $rows[] = $r;
            }

            $lastPage = (int) ($body['meta']['last_page'] ?? 1);
            $page++;
        } while ($page <= $lastPage && $page <= 50); // guard: не более 50 страниц

        return ['success' => true, 'rows' => $rows];
    }

    /**
     * Пакетная загрузка начислений и погашений сразу по многим партнёрам —
     * для админского списка «Партнёры» (этап В, PPM-W13).
     *
     * Одиночные `getAccruals()`/`getRedemptions()` делают 2 последовательных
     * HTTP на партнёра: на 50 партнёрах это 100 запросов в очередь. Здесь те же
     * эндпоинты зовутся через `Http::pool` чанками по {@see self::POOL_PARTNERS}
     * партнёров (×2 запроса = 10 одновременных). Первый раунд тянет страницу 1
     * и узнаёт `meta.last_page`, остальные страницы догружаются следующими
     * раундами — тем же пулом и с тем же пределом страниц, что у одиночных
     * методов.
     *
     * Флаг `PARTNER_ACCRUALS_FROM_BACKEND` — тот же: при выключенном флаге
     * отдаём пустые (но успешные) наборы, как это делает JoomlaCoupon::loadBackend().
     *
     * Частичный сбой изолирован по партнёру: у кого не догрузилось — у того
     * `success: false`, остальные приходят целыми. Разбор строк — общий с
     * одиночным путём (`JoomlaCoupon::classifyBackendRows()`).
     *
     * @param  array<int|string>  $partnerRefs
     * @return array<string,array{success:bool,accruals:array,redemptions:array}>
     */
    public function getAccrualsRedemptionsBatch(array $partnerRefs): array
    {
        $refs = array_values(array_unique(array_map('strval', $partnerRefs)));

        $result = [];
        foreach ($refs as $ref) {
            $result[$ref] = ['success' => true, 'accruals' => [], 'redemptions' => []];
        }

        if (empty($refs) || ! (bool) config('services.avicenna_backend.accruals_from_backend')) {
            return $result;
        }

        foreach (array_chunk($refs, self::POOL_PARTNERS) as $chunk) {
            // Раунд 1: первая страница обоих эндпоинтов по каждому партнёру.
            $descriptors = [];
            foreach ($chunk as $ref) {
                $descriptors[] = ['ref' => $ref, 'kind' => 'accruals', 'page' => 1];
                $descriptors[] = ['ref' => $ref, 'kind' => 'redemptions', 'page' => 1];
            }

            $lastPages = [];
            $this->runPoolRound($descriptors, $result, $lastPages);

            // Раунды 2+: доборы страниц там, где бэк сообщил last_page > 1.
            $pending = [];
            foreach ($lastPages as $key => $lastPage) {
                [$ref, $kind] = explode('|', $key, 2);
                if (($result[$ref]['success'] ?? false) === false) {
                    continue; // у партнёра уже сбой — добирать нечего
                }
                for ($page = 2; $page <= min($lastPage, self::MAX_PAGES); $page++) {
                    $pending[] = ['ref' => $ref, 'kind' => $kind, 'page' => $page];
                }
            }

            foreach (array_chunk($pending, self::POOL_PARTNERS * 2) as $round) {
                $ignored = [];
                $this->runPoolRound($round, $result, $ignored);
            }
        }

        return $result;
    }

    /**
     * Один раунд `Http::pool`: шлёт описанные запросы разом и раскладывает
     * строки по партнёрам. Сбой конкретного запроса помечает партнёра
     * `success: false` (детали — только в лог, наружу они не уходят).
     *
     * @param  array<array{ref:string,kind:string,page:int}>  $descriptors
     * @param  array<string,array{success:bool,accruals:array,redemptions:array}>  $result
     * @param  array<string,int>  $lastPages  Заполняется `ref|kind => meta.last_page`.
     */
    private function runPoolRound(array $descriptors, array &$result, array &$lastPages): void
    {
        if (empty($descriptors)) {
            return;
        }

        $cfg = config('services.avicenna_backend');
        $baseUrl = rtrim((string) $cfg['base_url'], '/');
        $timeout = (int) ($cfg['timeout'] ?? 10);
        $token = (string) $cfg['source_token'];

        $responses = Http::pool(function ($pool) use ($descriptors, $baseUrl, $timeout, $token) {
            $requests = [];

            foreach ($descriptors as $i => $d) {
                $requests[] = $pool->as((string) $i)
                    ->withHeaders(['X-Source-Token' => $token, 'Accept' => 'application/json'])
                    ->timeout($timeout)
                    ->get($baseUrl . '/api/v1/partner/' . $d['kind'], [
                        'partner_ref' => $d['ref'],
                        'per_page'    => 100,
                        'page'        => $d['page'],
                    ]);
            }

            return $requests;
        });

        foreach ($descriptors as $i => $d) {
            $ref = $d['ref'];
            $response = $responses[(string) $i] ?? null;

            if ($response instanceof \Throwable) {
                Log::error('avicenna_backend.batch_network_error', [
                    'error' => $response->getMessage(),
                    'partner_ref' => $ref,
                    'kind' => $d['kind'],
                    'page' => $d['page'],
                ]);
                $result[$ref]['success'] = false;
                continue;
            }

            if (! $response || ! $response->successful()) {
                Log::error('avicenna_backend.batch_failed', [
                    'status' => $response?->status(),
                    'partner_ref' => $ref,
                    'kind' => $d['kind'],
                    'page' => $d['page'],
                ]);
                $result[$ref]['success'] = false;
                continue;
            }

            $body = $response->json();

            foreach (($body['data'] ?? []) as $row) {
                $result[$ref][$d['kind']][] = $row;
            }

            if ($d['page'] === 1) {
                $lastPage = (int) ($body['meta']['last_page'] ?? 1);
                if ($lastPage > 1) {
                    $lastPages[$ref . '|' . $d['kind']] = $lastPage;
                }
            }
        }
    }
}
