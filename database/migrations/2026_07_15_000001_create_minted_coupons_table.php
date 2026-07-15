<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Журнал минтов партнёрских купонов через dual-write (Фаза D, этап 3).
 *
 * Живёт в СОБСТВЕННОЙ БД ppm (не Joomla). Назначение:
 *  - ретрай-идемпотентность: mint_request_id сохраняется ДО вызова бэка,
 *    сетевой ретрай (тот же партнёр+код) переиспользует id → бэк отвечает
 *    200 idempotent, а не 422;
 *  - ретрай Joomla-вставки: joomla_written=false помечает купоны, для которых
 *    бэк-минт прошёл, а старый INSERT в Joomla упал (фон-ретрай/алерт);
 *  - источник для UI-списков новых купонов (O2 плана) без чтения купонов с бэка.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minted_coupons', function (Blueprint $table) {
            $table->id();
            $table->string('partner_ref', 64)->index();   // ppm user id (Auth::id())
            $table->string('code');
            $table->string('kind', 16);                    // percent | bonus
            $table->integer('value');
            $table->integer('commission_percent')->nullable();
            $table->uuid('mint_request_id');
            $table->boolean('joomla_written')->default(false);
            $table->timestamps();

            // Один журнал-минт на (партнёр, код) — ключ ретрай-идемпотентности.
            $table->unique(['partner_ref', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minted_coupons');
    }
};
