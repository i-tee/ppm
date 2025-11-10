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
            // Переименовываем колонку phone_for_sbp в bank_phone_for_sbp
            $table->renameColumn('phone_for_sbp', 'bank_phone_for_sbp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requisites', function (Blueprint $table) {
            // Откатываем переименование обратно
            $table->renameColumn('bank_phone_for_sbp', 'phone_for_sbp');
        });
    }
};