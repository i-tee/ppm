<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Скрытый промокод — партнёр убрал его с глаз в ЛК (этап 1.6). Сам
 * промокод продолжает работать на сайте, признак только влияет на
 * отображение в кабинете (docs/cabinet-map.md §5, п.2).
 */
class HiddenCoupon extends Model
{
    protected $fillable = [
        'user_id',
        'coupon_code',
        'hidden_at',
    ];

    protected function casts(): array
    {
        return [
            'hidden_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Коды скрытых промокодов партнёра (нижний регистр). */
    public static function codesFor(int $userId): array
    {
        return self::where('user_id', $userId)->pluck('coupon_code')->all();
    }

    /** Скрыть код (идемпотентно — код уже хранится в нижнем регистре). */
    public static function hide(int $userId, string $code): void
    {
        self::firstOrCreate(
            ['user_id' => $userId, 'coupon_code' => mb_strtolower($code)],
            ['hidden_at' => now()]
        );
    }

    /** Вернуть код из архива (идемпотентно). */
    public static function restore(int $userId, string $code): void
    {
        self::where('user_id', $userId)
            ->where('coupon_code', mb_strtolower($code))
            ->delete();
    }
}
