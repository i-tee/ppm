<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrueBonusCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'joomla_user_id',
        'bonus_code_id',
        'bonus_code_value',
        'bonus_code_cost',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function createTrueBonusCode(array $data)
    {
        return self::create([
            'user_id' => $data['user_id'],
            'joomla_user_id' => $data['joomla_user_id'] ?? null,
            'bonus_code_id' => $data['bonus_code_id'],
            'bonus_code_value' => $data['bonus_code_value'],
            'bonus_code_cost' => $data['bonus_code_cost'],
        ]);
    }

    public static function updateTrueBonusCode($id, array $data)
    {
        $trueBonusCode = self::findOrFail($id);
        $trueBonusCode->update($data);
        return $trueBonusCode;
    }

    public static function deleteTrueBonusCode($id)
    {
        $trueBonusCode = self::findOrFail($id);
        return $trueBonusCode->delete();
    }

    public static function getTrueBonusCode($id)
    {
        return self::findOrFail($id);
    }

    public static function getAllTrueBonusCodes()
    {
        return self::all();
    }

    /**
     * Создает запись TrueBonusCode на основе данных из Joomla-купона.
     * Используется для интеграции с методом createCoupon в JoomlaCoupon.
     *
     * @param array $data Массив с данными: user_id (Laravel), joomla_user_id, bonus_code_id (coupon_id из Joomla),
     *                    bonus_code_value (coupon_code), bonus_code_cost (coupon_value/amount).
     * @return TrueBonusCode|bool Созданная модель или false в случае ошибки.
     */
    public static function createFromJoomlaCoupon(array $data)
    {
        try {
            // Валидация базовых данных (можно расширить при необходимости)
            if (empty($data['user_id']) || empty($data['joomla_user_id']) || empty($data['bonus_code_id']) ||
                empty($data['bonus_code_value']) || empty($data['bonus_code_cost'])) {
                return false;
            }

            // Используем существующий метод для создания
            return self::createTrueBonusCode($data);
        } catch (\Exception $e) {
            // Логируем ошибку (если нужно, добавьте Log::error здесь)
            return false;
        }
    }
}
