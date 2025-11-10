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
            // Переименовываем колонку card_number в bank_card_number
            $table->renameColumn('card_number', 'bank_card_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requisites', function (Blueprint $table) {
            // Откатываем переименование обратно (для безопасности)
            $table->renameColumn('bank_card_number', 'card_number');
        });
    }
};