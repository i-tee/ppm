<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('requisites', function (Blueprint $table) {
            // Паспортные данные (nullable, чтобы не ломать существующие NULL)
            $table->string('passport_series', 20)->nullable()->change();
            $table->string('passport_number', 20)->nullable()->change();
            $table->string('passport_issued_by_code', 20)->nullable()->change();
            $table->string('passport_snils', 20)->nullable()->change();

            // Банковские данные
            $table->string('bank_card_number', 20)->nullable()->change();
            $table->string('bank_phone_for_sbp', 20)->nullable()->change();
            $table->string('bank_account_number', 20)->nullable()->change();
            $table->string('bank_correspondent_account', 20)->nullable()->change();
            $table->string('bank_payment_account', 20)->nullable()->change();

            // Организационные идентификаторы
            $table->string('org_inn', 20)->nullable()->change();
            $table->string('org_ogrn', 20)->nullable()->change();
            $table->string('org_ogrnip', 20)->nullable()->change();
            $table->string('org_kpp', 20)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requisites', function (Blueprint $table) {
            // Откат: возвращаем к оригинальным длинам (nullable, если было)
            $table->string('passport_series', 4)->nullable()->change();
            $table->string('passport_number', 6)->nullable()->change();
            $table->string('passport_issued_by_code', 7)->nullable()->change();
            $table->string('passport_snils', 11)->nullable()->change();

            $table->string('bank_card_number', 16)->nullable()->change();
            $table->string('bank_phone_for_sbp', 15)->nullable()->change();
            $table->string('bank_account_number', 20)->nullable()->change();
            $table->string('bank_correspondent_account', 20)->nullable()->change();
            $table->string('bank_payment_account', 20)->nullable()->change();

            $table->string('org_inn', 12)->nullable()->change();
            $table->string('org_ogrn', 13)->nullable()->change();
            $table->string('org_ogrnip', 15)->nullable()->change();
            $table->string('org_kpp', 9)->nullable()->change();
        });
    }
};