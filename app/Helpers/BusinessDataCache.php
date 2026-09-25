<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

/**
 * Кеш ответов кабинета (`business-data`, `user/coupons`) на время TTL,
 * чтобы не бить по удалённой Joomla-БД на каждый заход в ЛК.
 * Явно `file`-стор (дефолтный CACHE_STORE на проде — `database`, то есть
 * та же удалённая БД, кешировать в неё же бессмысленно).
 *
 * См. docs/operations.md — раздел про кеш business-data.
 */
class BusinessDataCache
{
    /** Время жизни кеша, секунд. */
    public const TTL = 60;

    public static function businessDataKey(int $userId): string
    {
        return "ppm:business-data:{$userId}";
    }

    public static function couponsKey(int $userId): string
    {
        return "ppm:user-coupons:{$userId}";
    }

    /**
     * Сбрасывает кеш ответов кабинета конкретного пользователя.
     * Звать после любого действия, меняющего его баланс/купоны/выплаты/реквизиты:
     * создание купона, заявка на выплату, загрузка чека, админские правки
     * его выплат и реквизитов.
     */
    public static function forget(int $userId): void
    {
        Cache::store('file')->forget(self::businessDataKey($userId));
        Cache::store('file')->forget(self::couponsKey($userId));
    }
}
