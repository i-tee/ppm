<?php

namespace App\Models;

use App\Models\TrueBonusCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Helpers\Partners;
use App\Helpers\ErrorNotifier;
use App\Notifications\NewPercentCouponToCompanyNotification;
use App\Notifications\NewBonusCouponToCompanyNotification;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;
use Illuminate\Support\Arr;

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
     * Игнорируемые товарные группы
     * 5 - Наборы
     * 7 - Сертификаты
     * 8 - Уцененные товары
     */
    public static $ignore_groups = '8,5,7';

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

    public static function getCouponTypeById($couponId)
    {
        try {
            // Получаем купон по ID
            $coupon = DB::connection('mysql_joomla')
                ->table('jshopping_coupons')
                ->where('coupon_id', $couponId)
                ->first();

            // Если купон не найден, возвращаем ошибку
            if (!$coupon) {
                return [
                    'success' => false,
                    'message' => 'errors.coupon_not_found',
                    'error' => 'Coupon not found'
                ];
            }

            // Возвращаем тип купона
            return $coupon->coupon_type;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'errors.get_coupon_type_failed',
                'error' => $e->getMessage()
            ];
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

            // Модифицируем купоны: если coupon_value = 10, coupon_type = 0 и cashback = 0, устанавливаем cashback = 10
            $coupons = $coupons->map(function ($coupon) {
                if ($coupon->coupon_value == 10 && $coupon->coupon_type == 0 && $coupon->cashback == 0) {
                    $coupon->cashback = 10;
                }
                return $coupon;
            });

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

    // /**
    //  * Получает заказы, связанные с купоном, с определёнными статусами (7 или 6).
    //  * Возвращает массив заказов или пустой массив.
    //  *
    //  * @param int $couponId ID купона
    //  * @return array Массив заказов
    //  */
    // public static function getPpOrders($couponId)
    // {
    //     try {
    //         $couponId = (int) $couponId; // Защита от SQL-инъекции

    //         if ($couponId <= 0) {
    //             return [];
    //         }

    //         $orders = DB::connection('mysql_joomla')
    //             ->table('jshopping_orders')
    //             ->where('coupon_id', $couponId)
    //             ->whereIn('order_status', [6, 7])
    //             ->get()
    //             ->toArray(); // Конвертируем в обычный массив

    //         return $orders;
    //     } catch (\Exception $e) {
    //         Log::error("Failed to get PP orders", [
    //             'coupon_id' => $couponId,
    //             'error' => $e->getMessage(),
    //         ]);
    //         return [];
    //     }
    // }

    /**
     * Получает заказы, связанные с купоном, с определёнными статусами (7 или 6).
     * Возвращает массив заказов или пустой массив.
     * Дополнительно: для купонов типа 0 с cashback=0 и coupon_value=10,
     * модифицирует cashback заказа в возвращаемых данных (если cashback=0 и order_discount>0).
     *
     * @param int $couponId ID купона
     * @return array Массив заказов
     */
    public static function getPpOrders($couponId)
    {
        try {
            $couponId = (int) $couponId; // Защита от SQL-инъекции

            if ($couponId <= 0) {
                Log::warning("Invalid coupon ID", ['coupon_id' => $couponId]);
                return [];
            }

            // Получаем детали купона для проверки условий
            $coupon = DB::connection('mysql_joomla')
                ->table('jshopping_coupons')
                ->where('coupon_id', $couponId)
                ->first();

            if (!$coupon) {
                Log::warning("Coupon not found", ['coupon_id' => $couponId]);
                return [];
            }

            // Логируем данные купона для отладки
            Log::info("Checking coupon conditions", [
                'coupon_id' => $couponId,
                'coupon_type' => $coupon->coupon_type,
                'cashback' => $coupon->cashback,
                'coupon_value' => $coupon->coupon_value,
            ]);

            // Получаем заказы
            $orders = DB::connection('mysql_joomla')
                ->table('jshopping_orders')
                ->where('coupon_id', $couponId)
                ->whereIn('order_status', [6, 7])
                ->get();

            // Проверяем условия: coupon_type=0, cashback=0, coupon_value=10
            if ($coupon->coupon_type == 0 && (float) $coupon->cashback == 0.0 && (float) $coupon->coupon_value == 10.0) {
                Log::info("Coupon conditions met, checking orders for cashback update", [
                    'coupon_id' => $couponId,
                ]);
                $orders = $orders->map(function ($order) {
                    // Приводим cashback и order_discount к числу для точного сравнения
                    $orderCashback = (float) $order->cashback;
                    $orderDiscount = (float) $order->order_discount;

                    if ($orderCashback == 0.0 && $orderDiscount > 0) {
                        $order->cashback = number_format($orderDiscount, 2, '.', '');
                        Log::info("Updated order cashback", [
                            'order_id' => $order->order_id,
                            'cashback' => $order->cashback,
                            'order_discount' => $orderDiscount,
                        ]);
                    }
                    return $order;
                });
            } else {
                Log::info("Coupon conditions not met, skipping cashback update", [
                    'coupon_id' => $couponId,
                    'coupon_type' => $coupon->coupon_type,
                    'cashback' => $coupon->cashback,
                    'coupon_value' => $coupon->coupon_value,
                ]);
            }

            return $orders->toArray();
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
     * Возвращает массив с результатом операции и текстом ошибки (если есть).
     *
     * @param string $couponCode Код купона
     * @param int $amount Значение купона (процент или фикс-сумма)
     * @param int $userId ID пользователя в Joomla
     * @param int $coupon_type Тип купона: 0 - процентный или 1 - сумма
     * @return array ['success' => bool, 'error' => string|null] Успех операции и текст ошибки
     */
    public static function createCoupon($couponCode, $amount, $userId, $coupon_type = 0)
    {
        $couponCode = trim($couponCode);
        $amount = (int) $amount;
        $userId = (int) $userId;
        $coupon_type = (int) $coupon_type;

        $cooperation_type_agent = Partners::getSettings("cooperation_types")[1];
        $max_discount = (int) $cooperation_type_agent['max_discount'];
        $old_std_discount = (int) $cooperation_type_agent['old_std_discount'];
        $bonus_koef = $cooperation_type_agent['bonus_koef'];
        $cashback_percent = (int) ceil($max_discount - $amount);
        $finished_after_used = 0; // Промокод завершается после использования 0 - нет / 1 - да

        if (empty($couponCode) || $amount < 0 || $userId <= 0) {
            return ['success' => false, 'error' => 'coupon_invalid_params'];
        }

        // Проверка на существование купона с таким кодом
        if (DB::connection('mysql_joomla')->table('jshopping_coupons')->where('coupon_code', $couponCode)->exists()) {
            return ['success' => false, 'error' => 'coupon_code_exists'];
        }

        if (($coupon_type == 0 && $amount >= 100) || $amount < 0) {
            return ['success' => false, 'error' => 'coupon_invalid_amount_type'];
        }

        if (($coupon_type == 0 && $amount > $max_discount) || $amount < 0) {
            return ['success' => false, 'error' => 'coupon_invalid_amount_type'];
        }

        if ($coupon_type == 1) {

            $cost = $amount;
            $amount = ceil($amount * Partners::getSettings("cooperation_types")[1]['bonus_koef']);

            $cashback_percent = 0;
            $finished_after_used = 1;

            if ($amount <= 0) {

                return ['success' => false, 'error' => 'coupon_amount_null'];
            }

            if ($amount > 100000) {

                return ['success' => false, 'error' => 'coupon_amount_better_max'];
            }
        }

        // Формируем данные для вставки в jm_jshopping_coupons, включая все поля
        $couponData = [
            'coupon_type' => $coupon_type, // 0 - процент, 1 - сумма
            'coupon_code' => $couponCode, // Код купона
            'coupon_value' => number_format($amount, 2, '.', ''), // Форматируем как DECIMAL(12,2)
            'tax_id' => 0, // Обязательное поле, дефолт 0
            'used' => 0, // Количество использований
            'for_user_id' => 0, // ID пользователя джумла, на которого действует купон (0 - на всех)
            'coupon_start_date' => Carbon::now()->toDateString(), // Валидная дата, например, '2025-09-17'
            'coupon_expire_date' => Carbon::now()->addYears(10)->toDateString(), // Через 10 лет, например, '2035-09-17'
            'finished_after_used' => $finished_after_used,
            'coupon_publish' => 1, // Опубликовано
            'not_for_old_price' => 1, // Не для старых цен
            'not_for_different_prices' => 1, // Не для разных цен
            'min_amount' => '0.00', // DECIMAL, как в дампе
            'for_users_id' => '0', // Типичное значение
            'for_user_groups_id' => '0', // Типичное значение
            'for_products_id' => '0', // Типичное значение
            'for_categories_id' => '0', // Типичное значение
            'for_manufacturers_id' => '0', // Типичное значение
            'for_vendors_id' => '0', // Типичное значение
            'not_for_users_id' => '0', // Типичное значение
            'not_for_user_groups_id' => '0', // Типичное значение
            'not_for_products_id' => '0', // Типичное значение
            'not_for_categories_id' => self::$ignore_groups, // Игнорируемые категории
            'not_for_manufacturers_id' => '0', // Типичное значение
            'not_for_vendors_id' => '0', // Типичное значение
            'coupon_start_time' => 0, // Как в дампе
            'coupon_end_time' => 23, // Как в дампе
            'for_labels_id' => '0', // Типичное значение
            'not_for_labels_id' => '0', // Типичное значение
            'cashback' => $cashback_percent, // Обязательное поле, дефолт 0
        ];

        try {
            // Вставка купона в jshopping_coupons
            $newCouponId = DB::connection('mysql_joomla')
                ->table('jshopping_coupons')
                ->insertGetId($couponData); // Получаем ID созданного купона
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'coupon_insert_failed'];
        }

        if (!$newCouponId) {
            return ['success' => false, 'error' => 'coupon_insert_failed'];
        }

        $couponNotify = static::find($newCouponId);
        // Отправляем уведомление по типу
        if ($couponNotify->coupon_type === 0) {
            static::sendPercentCouponToCompany($couponNotify);
        } elseif ($couponNotify->coupon_type === 1) {
            static::sendBonusCouponToCompany($couponNotify);
        } else {
            Log::warning("[JoomlaCoupon] Неизвестный тип купона {$couponNotify->coupon_type} для ID {$couponNotify->coupon_id}. Уведомление не отправлено.");
        }

        // Связь с пользователем в jm_avicenna_user_coupons
        try {
            // Проверяем, существует ли запись для пользователя
            $existingRecord = DB::connection('mysql_joomla')
                ->table('avicenna_user_coupons')
                ->where('user_id', $userId)
                ->first();

            if ($existingRecord) {
                // Запись существует — добавляем ID купона к существующим (через запятую)
                $currentCoupons = $existingRecord->coupons;
                $newCouponIdStr = (string) $newCouponId;

                // Проверяем, не добавлен ли уже этот ID
                if (strpos($currentCoupons, $newCouponIdStr) === false) {
                    $updatedCoupons = $currentCoupons ? $currentCoupons . ',' . $newCouponIdStr : $newCouponIdStr;
                } else {
                    $updatedCoupons = $currentCoupons; // Уже существует, не добавляем
                }

                // Обновляем запись (не трогаем old_balance)
                $result = DB::connection('mysql_joomla')
                    ->table('avicenna_user_coupons')
                    ->where('user_id', $userId)
                    ->update(['coupons' => $updatedCoupons]);
            } else {
                // Запись не существует — создаём новую с old_balance = 0
                $userCouponData = [
                    'user_id' => $userId,
                    'coupons' => (string) $newCouponId, // ID купона как строка
                    'old_balance' => 0, // Дефолт для новой записи
                ];

                $result = DB::connection('mysql_joomla')
                    ->table('avicenna_user_coupons')
                    ->insert($userCouponData);
            }

            // Если купон успешно создан и тип == 1, создаем запись в TrueBonusCode
            if ($coupon_type == 1) {
                // Получаем Laravel user_id (предполагаем, что пользователь авторизован)
                $laravelUserId = Auth::user()->id ?? null;
                if ($laravelUserId) {
                    $trueBonusData = [
                        'user_id' => $laravelUserId,            // ID пользователя в Laravel
                        'joomla_user_id' => $userId,            // ID пользователя в Joomla
                        'bonus_code_id' => $newCouponId,        // ID купона из Joomla (bonus_code_id)
                        'bonus_code_value' => $amount,      // Значение промокода (coupon_code)
                        'bonus_code_cost' => $cost,           // Стоимость (coupon_value)
                    ];

                    // Вызываем специальный метод из TrueBonusCode
                    $trueBonusCreated = TrueBonusCode::createFromJoomlaCoupon($trueBonusData);

                    if (!$trueBonusCreated) {
                        // Если ошибка, можно откатить или просто залогировать (здесь не откатываем, чтобы не ломать Joomla-часть)
                        Log::error('Failed to create TrueBonusCode from JoomlaCoupon', [
                            'joomla_coupon_id' => $newCouponId,
                            'data' => $trueBonusData,
                        ]);
                        // Не прерываем успех Joomla-части, но можно добавить в return дополнительный ключ, если нужно
                    }
                } else {
                    Log::warning('No authenticated Laravel user for TrueBonusCode creation', [
                        'joomla_user_id' => $userId,
                    ]);
                }
            }

            if ($result) {
                return ['success' => true, 'error' => null];
            } else {
                return ['success' => false, 'error' => 'coupon_user_link_failed TRY'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'coupon_user_link_failed CASH'];
        }
    }

    public static function orders()
    {

        // 1. Получаем данные (то, что вы уже точно вызываете)
        $raw = JoomlaCoupon::getUserCoupons();   // может быть Collection, может быть массив

        // 2. Превращаем в коллекцию и берём только coupon_id
        $userCoupons = collect($raw['coupons'] ?? $raw)  // если вернулся массив вида ['coupons'=>[...]]
            ->pluck('coupon_id')
            ->values();

        $orders = collect([]);
        foreach ($userCoupons as $coupon_id) {
            $orders = $orders->merge(self::getPpOrders($coupon_id));
        }

        return response()->json([
            'orders' => $orders
        ], 200);
    }

    public static function withdrawals()
    {

        $withdrawals = self::getPpPayments(self::joomlaUser()->id);

        $debit = 0;

        foreach ($withdrawals as $withdrawal) {
            $debit += $withdrawal->summ;
        }

        return [
            'debit' => $debit,
            'withdrawals_count' => count($withdrawals), // Счётчик заказов
            'withdrawals' => $withdrawals
        ];
    }

    /**
     * Получает начисления по заказам пользователя, основываясь на методе orders().
     * Суммирует cashback для заказов, где cashback > 0.
     * Возвращает массив с общей суммой начислений, количеством заказов и списком заказов.
     *
     * @return array ['total_accruals' => float, 'orders_count' => int, 'orders' => array]
     */
    public static function credits()
    {
        try {
            // Получаем заказы через метод orders()
            $ordersResponse = self::orders();
            $orders = $ordersResponse->getData()->orders;

            // Рассчитываем сумму начислений
            $totalAccruals = 0.0;
            foreach ($orders as $order) {
                $cashback = (float) $order->cashback;
                if ($cashback > 0) {
                    $totalAccruals += $cashback;
                }
                // Пропускаем cashback <= 0, так как getPpOrders уже обработал cashback
            }

            // Логируем результат для отладки
            Log::info("Calculated credits", [
                'orders_count' => count($orders),
                'total_accruals' => round($totalAccruals, 2),
            ]);

            return [
                'total_accruals' => round($totalAccruals, 2), // Округляем до 2 знаков
                'orders_count' => count($orders), // Счётчик заказов
                'orders' => $orders // Массив заказов
            ];
        } catch (\Exception $e) {
            Log::error("Failed to calculate credits", [
                'error' => $e->getMessage(),
            ]);
            return [
                'total_accruals' => 0.0,
                'orders_count' => 0,
                'orders' => []
            ];
        }
    }

    /**
     * Получает сводную информацию по всем процентным купонам (type=0) пользователя.
     * Собирает данные по оплаченным заказам (status 6 или 7), рассчитывает кешбек,
     * и агрегирует метрики для каждого купона.
     *
     * @param int|null $joomlaUserId ID пользователя в Joomla (если null, берёт текущего авторизованного)
     * @return array Массив, где ключ - coupon_code, значение - ассоциативный массив с метриками
     */
    public static function getUserPercentCouponsSummary($joomlaUserId = null)
    {
        // Если не указан userId, берём текущего Joomla пользователя (из существующего метода)
        if ($joomlaUserId === null) {
            $joomlaUser = self::joomlaUser();
            if (!$joomlaUser) {
                return []; // Нет пользователя
            }
            $joomlaUserId = $joomlaUser->id;
        }

        // 1. Получаем все купоны пользователя из jm_avicenna_user_coupons
        $userCouponRecord = DB::connection('mysql_joomla')
            ->table('avicenna_user_coupons')
            ->where('user_id', $joomlaUserId)
            ->first();

        if (!$userCouponRecord || empty($userCouponRecord->coupons)) {
            return []; // Нет купонов
        }

        // Разбиваем строку coupons на массив ID
        $couponIds = array_filter(explode(',', $userCouponRecord->coupons));

        if (empty($couponIds)) {
            return [];
        }

        // 2. Получаем детали купонов из jm_jshopping_coupons, только type=0 (процентные)
        $coupons = DB::connection('mysql_joomla')
            ->table('jshopping_coupons')
            ->whereIn('coupon_id', $couponIds)
            ->where('coupon_type', 0) // Только процентные
            ->get()
            ->keyBy('coupon_code'); // Ключ по coupon_code для удобства

        if ($coupons->isEmpty()) {
            return [];
        }

        // 3. Для каждого купона собираем сводку
        $summary = [];

        foreach ($coupons as $couponCode => $coupon) {
            // Получаем заказы по купону (используем существующий метод getPpOrders)
            $orders = self::getPpOrders($coupon->coupon_id);

            // Фильтруем только оплаченные (status 6 или 7) и с валидным cashback
            $filteredOrders = [];
            $totalCashback = 0.0;
            $totalDiscount = 0.0;
            $totalOrderSum = 0.0;
            $totalSubtotal = 0.0; // Сумма без скидок
            $usageCount = 0;

            foreach ($orders as $order) {
                if (!in_array($order->order_status, [6, 7])) {
                    continue; // Не оплаченный
                }

                $cashback = (float) $order->cashback;
                $discount = (float) $order->order_discount;

                if ($cashback < 0) {
                    continue; // Пропускаем отрицательный cashback (бонусный промокод)
                } elseif ($cashback == 0 && $discount > 0) {
                    $cashback = $discount; // Если 0 и есть discount, то cashback = discount
                } elseif ($cashback > 0) {
                    // Используем указанный cashback
                } else {
                    continue; // Если cashback == 0 и discount <=0, пропускаем (бесполезный заказ)
                }

                // Агрегируем метрики
                $totalCashback += $cashback;
                $totalDiscount += $discount;
                $totalOrderSum += (float) $order->order_total;
                $totalSubtotal += (float) $order->order_subtotal;
                $usageCount++;

                // Сохраняем детали заказа для возможной детализации (опционально, но полезно)
                $filteredOrders[] = [
                    'order_id' => $order->order_id,
                    'order_number' => $order->order_number,
                    'order_total' => (float) $order->order_total,
                    'order_discount' => $discount,
                    'cashback' => $cashback,
                    'order_date' => $order->order_date,
                    'order_status' => $order->order_status,
                    // Добавьте другие поля из заказа, если нужно (например, user_id, ip_address и т.д.)
                ];
            }

            // Сводка по купону
            $summary[$couponCode] = [
                'coupon_id' => $coupon->coupon_id,
                'coupon_value' => (float) $coupon->coupon_value, // Процент скидки
                'coupon_expire_date' => $coupon->coupon_expire_date,
                'usage_count' => $usageCount, // Количество применений (оплаченных заказов)
                'total_cashback' => round($totalCashback, 2), // Суммарный кешбек агенту
                'total_discount' => round($totalDiscount, 2), // Суммарная скидка покупателям
                'total_order_sum' => round($totalOrderSum, 2), // Сумма сделанных заказов (order_total)
                'total_subtotal' => round($totalSubtotal, 2), // Сумма без скидок (order_subtotal) - полезно для понимания маржи
                'average_order_value' => $usageCount > 0 ? round($totalOrderSum / $usageCount, 2) : 0, // Средний чек
                'average_discount' => $usageCount > 0 ? round($totalDiscount / $usageCount, 2) : 0, // Средняя скидка на заказ
                'average_cashback' => $usageCount > 0 ? round($totalCashback / $usageCount, 2) : 0, // Средний кешбек на заказ
                'orders' => $filteredOrders, // Детальный список заказов (для интерфейса, если нужно развернуть)
            ];
        }

        return $summary;
    }

    /**
     * Получает всю информацию о заказе(ах), сделанных с использованием купона по его ID.
     *
     * @param int $coupon_id ID купона в Joomla
     * @return \Illuminate\Support\Collection Коллекция заказов с полной информацией
     */
    public static function getOrderInfoByCouponId($coupon_id)
    {
        // Используем существующий метод getPpOrders для получения заказов
        $orders = self::getPpOrders((int) $coupon_id);

        // Преобразуем массив обратно в коллекцию для совместимости
        return collect($orders);
    }

    /* Уведомления ------------------------------------------------------------------------------------------------------------------------------------ */
    /**
     * Отправляет уведомление о процентном купоне (type=0)
     */
    protected static function sendPercentCouponToCompany(JoomlaCoupon $coupon): void
    {
        try {
            // Получаем email из настроек (как в observer)
            $globalSettings = Partners::getSettings('global');
            $email = Arr::get($globalSettings, 'responsible_partnersmail') ?: Arr::get($globalSettings, 'global.responsible_partnersmail');

            if (!$email) {
                ErrorNotifier::notify('Почта компании не найдена для уведомления о процентном купоне');
                Log::error("[JoomlaCoupon] Email не найден для купона {$coupon->coupon_id}.");
                return;
            }

            Log::info("[JoomlaCoupon] Отправка процентного уведомления на $email для купона {$coupon->coupon_id}.");

            Notification::route('mail', $email)->notify(new NewPercentCouponToCompanyNotification($coupon));

            Log::info("[JoomlaCoupon] Уведомление о процентном купоне отправлено на $email.");
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            ErrorNotifier::notify('Ошибка отправки уведомления о процентном купоне: ' . $errorMsg);
            Log::error("[JoomlaCoupon] Исключение: $errorMsg.");
        }
    }

    /**
     * Отправляет уведомление о бонусном купоне (type=1)
     */
    protected static function sendBonusCouponToCompany(JoomlaCoupon $coupon): void
    {
        try {
            // Аналогично для бонусного
            $globalSettings = Partners::getSettings('global');
            $email = Arr::get($globalSettings, 'responsible_partnersmail') ?: Arr::get($globalSettings, 'global.responsible_partnersmail');

            if (!$email) {
                ErrorNotifier::notify('Почта компании не найдена для уведомления о бонусном купоне');
                Log::error("[JoomlaCoupon] Email не найден для купона {$coupon->coupon_id}.");
                return;
            }

            Log::info("[JoomlaCoupon] Отправка бонусного уведомления на $email для купона {$coupon->coupon_id}.");

            Notification::route('mail', $email)->notify(new NewBonusCouponToCompanyNotification($coupon));

            Log::info("[JoomlaCoupon] Уведомление о бонусном купоне отправлено на $email.");
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            ErrorNotifier::notify('Ошибка отправки уведомления о бонусном купоне: ' . $errorMsg);
            Log::error("[JoomlaCoupon] Исключение: $errorMsg.");
        }
    }
}
