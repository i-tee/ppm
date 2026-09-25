<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Скрытые промокоды (этап 1.6) — только отображение в ЛК, сам промокод
 * продолжает работать на сайте и начисления идут как раньше. Живёт в
 * СВОЕЙ БД ppm, Joomla и основной бэкенд не трогает (решение владельца,
 * docs/cabinet-map.md §5, п.2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hidden_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('coupon_code'); // храним в нижнем регистре
            $table->timestamp('hidden_at');
            $table->timestamps();

            $table->unique(['user_id', 'coupon_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hidden_coupons');
    }
};
