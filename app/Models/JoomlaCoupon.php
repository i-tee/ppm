<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JoomlaCoupon extends Model
{
    /**
     * Соединение с БД Joomla
     */
    protected $connection = 'mysql_joomla';

    /**
     * Таблица в БД Joomla
     */
    protected $table = 'jshopping_coupons';

    /**
     * Первичный ключ
     */
    protected $primaryKey = 'coupon_id';

    /**
     * Отключаем автоинкремент, если он не нужен
     */
    public $incrementing = true;

    /**
     * Тип первичного ключа
     */
    protected $keyType = 'int';

    /**
     * Отключаем временные метки
     */
    public $timestamps = false;

    /**
     * Атрибуты, которые можно массово заполнять
     */
    protected $fillable = [
        'coupon_type',
        'coupon_code',
        'coupon_value',
        'tax_id',
        'used', // 0 - не использован, 1 - использован
        'for_user_id', // ID пользователя Joomla, для которого купон
        'coupon_start_date',
        'coupon_expire_date',
        'finished_after_used', // 0 - нет, 1 - да (завершить после использования)
        'coupon_publish', // 0 - не опубликован, 1 - опубликован
        'not_for_old_price', // 0 - нет, 1 - да
        'not_for_different_prices', // 0 - нет, 1 - да
        'min_amount', // Минимальная сумма заказа
        'for_users_id', // Строка с ID пользователей, для которых купон (например, "1,2,3")
        'for_user_groups_id', // Строка с ID групп пользователей
        'for_products_id', // Строка с ID продуктов
        'for_categories_id', // Строка с ID категорий
        'for_manufacturers_id', // Строка с ID производителей
        'for_vendors_id', // Строка с ID поставщиков
        'not_for_users_id', // Строка с ID пользователей, для которых купон НЕ действует
        'not_for_user_groups_id', // Строка с ID групп, для которых купон НЕ действует
        'not_for_products_id', // Строка с ID продуктов, для которых купон НЕ действует
        'not_for_categories_id', // Строка с ID категорий, для которых купон НЕ действует
        'not_for_manufacturers_id', // Строка с ID производителей, для которых купон НЕ действует
        'not_for_vendors_id', // Строка с ID поставщиков, для которых купон НЕ действует
        'coupon_start_time', // Время начала действия (например, "00:00:00")
        'coupon_end_time', // Время окончания действия (например, "23:59:59")
        'for_labels_id', // Строка с ID меток
        'not_for_labels_id', // Строка с ID меток, для которых купон НЕ действует
    ];

    /**
     * Атрибуты, которые должны быть преобразованы в даты
     */
    protected $dates = [
        'coupon_start_date',
        'coupon_expire_date',
    ];

    /**
     * Преобразование строковых списков ID в массивы и обратно
     * Это нужно для полей типа "1,2,3"
     */

    // --- Accessors (при получении из БД) ---
    public function getForUsersIdAttribute($value)
    {
        return $value ? explode(',', $value) : [];
    }

    public function getForUserGroupsIdAttribute($value)
    {
        return $value ? explode(',', $value) : [];
    }

    public function getForProductsIdAttribute($value)
    {
        return $value ? explode(',', $value) : [];
    }

    public function getForCategoriesIdAttribute($value)
    {
        return $value ? explode(',', $value) : [];
    }

    public function getForManufacturersIdAttribute($value)
    {
        return $value ? explode(',', $value) : [];
    }

    public function getForVendorsIdAttribute($value)
    {
        return $value ? explode(',', $value) : [];
    }

    public function getNotForUsersIdAttribute($value)
    {
        return $value ? explode(',', $value) : [];
    }

    public function getNotForUserGroupsIdAttribute($value)
    {
        return $value ? explode(',', $value) : [];
    }

    public function getNotForProductsIdAttribute($value)
    {
        return $value ? explode(',', $value) : [];
    }

    public function getNotForCategoriesIdAttribute($value)
    {
        return $value ? explode(',', $value) : [];
    }

    public function getNotForManufacturersIdAttribute($value)
    {
        return $value ? explode(',', $value) : [];
    }

    public function getNotForVendorsIdAttribute($value)
    {
        return $value ? explode(',', $value) : [];
    }

    public function getForLabelsIdAttribute($value)
    {
        return $value ? explode(',', $value) : [];
    }

    public function getNotForLabelsIdAttribute($value)
    {
        return $value ? explode(',', $value) : [];
    }

    // --- Mutators (при записи в БД) ---
    public function setForUsersIdAttribute($value)
    {
        $this->attributes['for_users_id'] = is_array($value) ? implode(',', $value) : $value;
    }

    public function setForUserGroupsIdAttribute($value)
    {
        $this->attributes['for_user_groups_id'] = is_array($value) ? implode(',', $value) : $value;
    }

    public function setForProductsIdAttribute($value)
    {
        $this->attributes['for_products_id'] = is_array($value) ? implode(',', $value) : $value;
    }

    public function setForCategoriesIdAttribute($value)
    {
        $this->attributes['for_categories_id'] = is_array($value) ? implode(',', $value) : $value;
    }

    public function setForManufacturersIdAttribute($value)
    {
        $this->attributes['for_manufacturers_id'] = is_array($value) ? implode(',', $value) : $value;
    }

    public function setForVendorsIdAttribute($value)
    {
        $this->attributes['for_vendors_id'] = is_array($value) ? implode(',', $value) : $value;
    }

    public function setNotForUsersIdAttribute($value)
    {
        $this->attributes['not_for_users_id'] = is_array($value) ? implode(',', $value) : $value;
    }

    public function setNotForUserGroupsIdAttribute($value)
    {
        $this->attributes['not_for_user_groups_id'] = is_array($value) ? implode(',', $value) : $value;
    }

    public function setNotForProductsIdAttribute($value)
    {
        $this->attributes['not_for_products_id'] = is_array($value) ? implode(',', $value) : $value;
    }

    public function setNotForCategoriesIdAttribute($value)
    {
        $this->attributes['not_for_categories_id'] = is_array($value) ? implode(',', $value) : $value;
    }

    public function setNotForManufacturersIdAttribute($value)
    {
        $this->attributes['not_for_manufacturers_id'] = is_array($value) ? implode(',', $value) : $value;
    }

    public function setNotForVendorsIdAttribute($value)
    {
        $this->attributes['not_for_vendors_id'] = is_array($value) ? implode(',', $value) : $value;
    }

    public function setForLabelsIdAttribute($value)
    {
        $this->attributes['for_labels_id'] = is_array($value) ? implode(',', $value) : $value;
    }

    public function setNotForLabelsIdAttribute($value)
    {
        $this->attributes['not_for_labels_id'] = is_array($value) ? implode(',', $value) : $value;
    }

    // --- Скоупы для удобного поиска ---

    /**
     * Скоуп для получения активных купонов
     */
    public function scopeActive($query)
    {
        return $query->where('coupon_publish', 1)
            ->where(function ($q) {
                $q->whereNull('coupon_start_date')
                    ->orWhere('coupon_start_date', '<=', now()->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('coupon_expire_date')
                    ->orWhere('coupon_expire_date', '>=', now()->toDateString());
            })
            ->where('used', 0);
    }

    /**
     * Скоуп для получения купонов по коду
     */
    public function scopeByCode($query, $code)
    {
        return $query->where('coupon_code', $code);
    }

    /**
     * Скоуп для получения купонов, применимых ко всем пользователям
     */
    public function scopeForAllUsers($query)
    {
        return $query->where('for_user_id', 0)
            ->where('for_users_id', '');
    }

    // --- Вспомогательные методы ---

    /**
     * Проверить, действителен ли купон
     */
    public function isValid()
    {
        // Проверка публикации
        if ($this->coupon_publish != 1) {
            return false;
        }

        // Проверка даты начала
        if ($this->coupon_start_date && $this->coupon_start_date->isFuture()) {
            return false;
        }

        // Проверка даты окончания
        if ($this->coupon_expire_date && $this->coupon_expire_date->isPast()) {
            return false;
        }

        // Проверка использования
        if ($this->used == 1 && $this->finished_after_used == 1) {
            return false;
        }

        return true;
    }

    /**
     * Проверить, применим ли купон к конкретному пользователю Joomla
     * @param int $userId ID пользователя в Joomla
     */
    public function isApplicableToUser($userId)
    {
        // Если купон для конкретного пользователя
        if ($this->for_user_id && $this->for_user_id == $userId) {
            return true;
        }

        // Если купон для списка пользователей
        if (!empty($this->for_users_id) && in_array($userId, $this->for_users_id)) {
            return true;
        }

        // Если купон исключает конкретных пользователей
        if (!empty($this->not_for_users_id) && in_array($userId, $this->not_for_users_id)) {
            return false;
        }

        // Если купон для всех
        if ($this->for_user_id == 0 && empty($this->for_users_id)) {
            return true;
        }

        return false;
    }

    /**
     * Отметить купон как использованный
     */
    public function markAsUsed()
    {
        if ($this->finished_after_used == 1) {
            $this->used = 1;
            $this->save();
        }
    }

    public static function dev()
    {
        return Auth::user()->email;
    }

    public static function getUserCoupons()
    {

        try {
            // Получаем email текущего пользователя Laravel
            $userEmail = Auth::user()->email;

            // Ищем пользователя в Joomla по email
            $joomlaUser = DB::connection('mysql_joomla')
                ->table('users')
                ->where('email', $userEmail)
                ->first();

            if (!$joomlaUser) {
                return [
                    'success' => false,
                    'message' => 'Пользователь не найден в Joomla'
                ];
            }

            // Получаем запись из avicenna_user_coupons
            $userCouponRecord = DB::connection('mysql_joomla')
                ->table('avicenna_user_coupons')
                ->where('user_id', $joomlaUser->id)
                ->first();

            if (!$userCouponRecord) {
                return [
                    'success' => false,
                    'message' => 'Запись купонов не найдена'
                ];
            }

            // Разбираем строку coupons в массив ID
            $couponIds = array_map('intval', explode(',', $userCouponRecord->coupons));
            $couponIds = array_filter($couponIds, fn($id) => $id > 0); // Убираем пустые значения

            // Получаем сами купоны из jm_jshopping_coupons
            $coupons = DB::connection('mysql_joomla')
                ->table('jshopping_coupons')
                ->whereIn('coupon_id', $couponIds)
                ->get();

            return [
                'success' => true,
                'joomla_user_id' => $joomlaUser->id,
                'coupons' => $coupons,
                'coupons_count' => $coupons->count()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка при получении купонов',
                'error' => $e->getMessage()
            ];
        }
    }
}
