<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

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
        'used',
        'for_user_id',
        'coupon_start_date',
        'coupon_expire_date',
        'finished_after_used',
        'coupon_publish',
        'not_for_old_price',
        'not_for_different_prices',
        'min_amount',
        'for_users_id',
        'for_user_groups_id',
        'for_products_id',
        'for_categories_id',
        'for_manufacturers_id',
        'for_vendors_id',
        'not_for_users_id',
        'not_for_user_groups_id',
        'not_for_products_id',
        'not_for_categories_id',
        'not_for_manufacturers_id',
        'not_for_vendors_id',
        'coupon_start_time',
        'coupon_end_time',
        'for_labels_id',
        'not_for_labels_id',
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
     */

    // --- Accessors ---
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

    // --- Mutators ---
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

    // --- Скоупы ---

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

    public function scopeByCode($query, $code)
    {
        return $query->where('coupon_code', $code);
    }

    public function scopeForAllUsers($query)
    {
        return $query->where('for_user_id', 0)
            ->where('for_users_id', '');
    }

    // --- Вспомогательные методы ---

    public function isValid()
    {
        if ($this->coupon_publish != 1) {
            return false;
        }

        if ($this->coupon_start_date && $this->coupon_start_date->isFuture()) {
            return false;
        }

        if ($this->coupon_expire_date && $this->coupon_expire_date->isPast()) {
            return false;
        }

        if ($this->used == 1 && $this->finished_after_used == 1) {
            return false;
        }

        return true;
    }

    public function isApplicableToUser($userId)
    {
        if ($this->for_user_id && $this->for_user_id == $userId) {
            return true;
        }

        if (!empty($this->for_users_id) && in_array($userId, $this->for_users_id)) {
            return true;
        }

        if (!empty($this->not_for_users_id) && in_array($userId, $this->not_for_users_id)) {
            return false;
        }

        if ($this->for_user_id == 0 && empty($this->for_users_id)) {
            return true;
        }

        return false;
    }

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

    /**
     * Создает нового пользователя в таблице Joomla users
     *
     * @param string $email
     * @param string $name
     * @return object|null
     */
    public static function createJoomlaUser($email, $name)
    {
        try {
            // Логируем входные данные
            Log::info("Attempting to create Joomla user", [
                'email' => $email,
                'name' => $name,
            ]);

            // Проверяем, существует ли пользователь с таким email
            $existingUserByEmail = DB::connection('mysql_joomla')
                ->table('users') // Префикс 'jm_' из config, итого 'jm_users'
                ->where('email', $email)
                ->first();

            if ($existingUserByEmail) {
                Log::info("User with email {$email} already exists in Joomla", [
                    'user_id' => $existingUserByEmail->id,
                ]);
                return $existingUserByEmail;
            }

            // Генерируем случайный пароль
            $randomPassword = Str::random(12);
            $hashedPassword = bcrypt($randomPassword);

            // Генерируем уникальный username
            $usernameBase = explode('@', $email)[0];
            $username = $usernameBase . '_avi_' . Str::random(4);

            // Проверяем уникальность username
            $existingUserByUsername = DB::connection('mysql_joomla')
                ->table('users')
                ->where('username', $username)
                ->first();
            if ($existingUserByUsername) {
                $username .= '_' . Str::random(2);
                // Дополнительная проверка
                $existingUserByUsername = DB::connection('mysql_joomla')
                    ->table('users')
                    ->where('username', $username)
                    ->first();
                if ($existingUserByUsername) {
                    $username = $usernameBase . '_avi_' . Str::random(6);
                }
            }

            // Подготовка данных: обязательные поля + lastvisitDate и lastResetTime для обхода NOT NULL
            $userData = [
                'name' => $name ?: 'User',
                'username' => $username,
                'email' => $email,
                'password' => $hashedPassword,
                'block' => 0,
                'sendEmail' => 1,
                'registerDate' => now()->toDateTimeString(),
                'lastvisitDate' => now()->toDateTimeString(),
                'lastResetTime' => now()->toDateTimeString(),
                'activation' => '',
                'params' => '{"activate":0}',
            ];

            // Логируем данные перед вставкой
            Log::info("Prepared user data for insertion", $userData);

            // Создаем пользователя
            $newUserId = DB::connection('mysql_joomla')
                ->table('users')
                ->insertGetId($userData);

            Log::info("Created new Joomla user", [
                'user_id' => $newUserId,
                'email' => $email,
                'username' => $username,
            ]);

            // Возвращаем созданного пользователя
            return DB::connection('mysql_joomla')
                ->table('users')
                ->where('id', $newUserId)
                ->first();
        } catch (\Exception $e) {
            Log::error("Failed to create Joomla user", [
                'email' => $email,
                'name' => $name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Создает запись в avicenna_user_coupons для пользователя
     *
     * @param int $userId
     * @return bool
     */
    public static function ensureUserCouponRecord($userId)
    {
        try {
            $userCouponRecord = DB::connection('mysql_joomla')
                ->table('avicenna_user_coupons')
                ->where('user_id', $userId)
                ->first();

            if (!$userCouponRecord) {
                DB::connection('mysql_joomla')
                    ->table('avicenna_user_coupons')
                    ->insert([
                        'user_id' => $userId,
                        'coupons' => '',
                    ]);
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function joomlaUser()
    {
         try {
            // Получаем пользователя в Joomla по email текущего пользователя Laravel
            $userEmail = Auth::user()->email;

            // Ищем пользователя в Joomla
            $joomlaUser = DB::connection('mysql_joomla')
                ->table('users')
                ->where('email', $userEmail)
                ->first();
            return $joomlaUser;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Получает купоны пользователя
     *
     * @return array
     */
    public static function getUserCoupons()
    {
        try {
            // Получаем email и имя текущего пользователя Laravel
            $userEmail = Auth::user()->email;
            $userName = Auth::user()->name ?? 'User';

            // Ищем пользователя в Joomla
            $joomlaUser = DB::connection('mysql_joomla')
                ->table('users')
                ->where('email', $userEmail)
                ->first();

            // Если пользователь не найден, создаем его
            if (!$joomlaUser) {
                $joomlaUser = self::createJoomlaUser($userEmail, $userName);
                if (!$joomlaUser) {
                    return [
                        'success' => false,
                        'message' => 'errors.user_creation_failed',
                        'error' => 'Failed to create Joomla user'
                    ];
                }
            }

            // Получаем запись купонов (если нет - null)
            $userCouponRecord = DB::connection('mysql_joomla')
                ->table('avicenna_user_coupons')
                ->where('user_id', $joomlaUser->id)
                ->first();

            // Если записи нет, возвращаем пустой список купонов
            if (!$userCouponRecord) {
                return [
                    'success' => true,
                    'joomla_user_id' => $joomlaUser->id,
                    'coupons' => collect(),
                    'coupons_count' => 0
                ];
            }

            // Разбираем строку coupons в массив ID
            $couponIds = array_map('intval', explode(',', $userCouponRecord->coupons));
            $couponIds = array_filter($couponIds, fn($id) => $id > 0);

            // Получаем купоны
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
                'message' => 'errors.get_coupons_failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Получает купоны пользователя из таблицы avicenna_user_coupons по ID Joomla пользователя.
     * Возвращает массив записей с купонами или пустой массив, если ничего не найдено.
     *
     * @param int $userId ID пользователя в Joomla
     * @return array Массив купонов (каждый - ассоциативный массив с данными из таблицы)
     */
    public static function getPpCoupons($userId)
    {
        try {
            $userId = (int) $userId; // Защита от SQL-инъекции

            $coupons = DB::connection('mysql_joomla')
                ->table('avicenna_user_coupons')
                ->where('user_id', $userId)
                ->get()
                ->toArray(); // Конвертируем в обычный массив для совместимости

            return $coupons;
        } catch (\Exception $e) {
            Log::error("Failed to get PP coupons", [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Проверяет существование купона по коду в таблице jshopping_coupons.
     * Возвращает ID купона, если найден, или false/null, если не найден.
     *
     * @param string $couponCode Код купона для проверки
     * @return int|false ID купона или false
     */
    public static function checkCoupons($couponCode)
    {
        try {
            $couponCode = trim($couponCode); // Очистка и защита

            if (empty($couponCode)) {
                return false;
            }

            $couponId = DB::connection('mysql_joomla')
                ->table('jshopping_coupons')
                ->where('coupon_code', $couponCode)
                ->value('coupon_id');

            return $couponId ?: false;
        } catch (\Exception $e) {
            Log::error("Failed to check coupons", [
                'coupon_code' => $couponCode,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Получает код (имя) купона по его ID из таблицы jshopping_coupons.
     * Возвращает код купона или пустую строку, если не найден.
     *
     * @param int $couponId ID купона
     * @return string Код купона
     */
    public static function getCouponName($couponId)
    {
        try {
            $couponId = (int) $couponId; // Защита от SQL-инъекции

            if ($couponId <= 0) {
                return '';
            }

            $couponCode = DB::connection('mysql_joomla')
                ->table('jshopping_coupons')
                ->where('coupon_id', $couponId)
                ->value('coupon_code');

            return $couponCode ?: '';
        } catch (\Exception $e) {
            Log::error("Failed to get coupon name", [
                'coupon_id' => $couponId,
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * Проверяет наличие старого баланса промокода для пользователя.
     * Возвращает массив с 'be' (true/false) и 'summ' (сумма, если >0).
     *
     * @param int $userId ID пользователя в Joomla
     * @return array ['be' => bool, 'summ' => int|null]
     */
    public static function oldPromocodBalance($userId)
    {
        try {
            $userId = (int) $userId; // Защита от SQL-инъекции

            if ($userId <= 0) {
                return ['be' => false];
            }

            $oldBalance = DB::connection('mysql_joomla')
                ->table('avicenna_user_coupons')
                ->where('user_id', $userId)
                ->value('old_balance');

            $oldBalance = (int) $oldBalance;

            return [
                'be' => $oldBalance > 0,
                'summ' => $oldBalance > 0 ? $oldBalance : null
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get old promocod balance", [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return ['be' => false];
        }
    }

    /**
     * Получает заказы, связанные с купоном, с определёнными статусами (7 или 6).
     * Возвращает массив заказов или пустой массив.
     *
     * @param int $couponId ID купона
     * @return array Массив заказов
     */
    public static function getPpOrders($couponId)
    {
        try {
            $couponId = (int) $couponId; // Защита от SQL-инъекции

            if ($couponId <= 0) {
                return [];
            }

            $orders = DB::connection('mysql_joomla')
                ->table('jshopping_orders')
                ->where('coupon_id', $couponId)
                ->whereIn('order_status', [6, 7])
                ->get()
                ->toArray(); // Конвертируем в обычный массив

            return $orders;
        } catch (\Exception $e) {
            Log::error("Failed to get PP orders", [
                'coupon_id' => $couponId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Получает платежи из таблицы avicenna_pp_payments.
     * Если $joomlaUserId указан, фильтрует по пользователю; иначе возвращает все.
     *
     * @param int|null $userId ID пользователя (опционально)
     * @return array Массив платежей
     */
    public static function getPpPayments($joomlaUserId = null)
    {
        try {
            $query = DB::connection('mysql_joomla')
                ->table('avicenna_pp_payments');

            if ($joomlaUserId !== null) {
                $joomlaUserId = (int) $joomlaUserId; // Защита от SQL-инъекции
                if ($joomlaUserId > 0) {
                    $query->where('user_id', $joomlaUserId);
                }
            }

            $payments = $query->get()
                ->toArray(); // Конвертируем в обычный массив

            return $payments;
        } catch (\Exception $e) {
            Log::error("Failed to get PP payments", [
                'user_id' => $joomlaUserId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Создаёт новый купон в jshopping_coupons и связывает его с пользователем в avicenna_user_coupons.
     * Возвращает true при успехе, false при ошибке.
     *
     * @param string $couponCode Код купона
     * @param int $procent Значение купона (процент)
     * @param int $userId ID пользователя в Joomla
     * @return bool Успех операции
     */
    public static function creatCoupons($couponCode, $procent, $userId)
    {
        try {
            $couponCode = trim($couponCode);
            $procent = (int) $procent;
            $userId = (int) $userId;

            if (empty($couponCode) || $procent <= 0 || $userId <= 0) {
                Log::warning("Invalid parameters for creating coupons", [
                    'coupon_code' => $couponCode,
                    'procent' => $procent,
                    'user_id' => $userId,
                ]);
                return false;
            }

            // Вставка в jshopping_coupons
            $couponData = [
                'coupon_type' => 0,
                'coupon_code' => $couponCode,
                'coupon_value' => $procent,
                'coupon_publish' => 1,
                'not_for_old_price' => 1,
                'not_for_different_prices' => 1,
                'not_for_categories_id' => '5,7',
                'coupon_end_time' => 23, // Время окончания (часы? Из оригинала)
            ];

            $newCouponId = DB::connection('mysql_joomla')
                ->table('jshopping_coupons')
                ->insertGetId($couponData);

            if (!$newCouponId) {
                Log::error("Failed to insert coupon", [
                    'coupon_code' => $couponCode,
                ]);
                return false;
            }

            // Вставка в avicenna_user_coupons
            $userCouponData = [
                'user_id' => $userId,
                'coupons' => (string) $newCouponId, // Как строка ID
            ];

            $result = DB::connection('mysql_joomla')
                ->table('avicenna_user_coupons')
                ->insert($userCouponData);

            if ($result) {
                Log::info("Created coupon and linked to user", [
                    'coupon_id' => $newCouponId,
                    'user_id' => $userId,
                ]);
                return true;
            } else {
                Log::error("Failed to link coupon to user", [
                    'coupon_id' => $newCouponId,
                    'user_id' => $userId,
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Failed to create coupons", [
                'coupon_code' => $couponCode,
                'procent' => $procent,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

}
