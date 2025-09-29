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
        Schema::create('requisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Связь с пользователем (партнером)
            $table->integer('partner_type_id'); // ID типа партнера (1: individual, 2: self-employed, 3: entrepreneur, 4: company)
            $table->string('full_name')->nullable(); // ФИО для физлиц/самозанятых или ФИО директора/владельца для ИП/ООО
            $table->string('organization_name')->nullable(); // Название организации для ИП/ООО
            $table->string('inn')->nullable(); // ИНН (общий для всех, кроме физлиц без ИНН)
            $table->string('ogrn')->nullable(); // ОГРН/ОГРНИП для ИП/ООО
            $table->string('kpp')->nullable(); // КПП для ООО
            $table->string('legal_address')->nullable(); // Юридический адрес
            $table->string('postal_address')->nullable(); // Почтовый адрес (если отличается)
            $table->string('bank_name')->nullable(); // Название банка
            $table->string('bik')->nullable(); // БИК банка
            $table->string('correspondent_account')->nullable(); // Корреспондентский счет
            $table->string('payment_account')->nullable(); // Расчетный счет (для ИП/ООО)
            $table->string('card_number')->nullable(); // Номер карты (для физлиц/самозанятых)
            $table->string('phone_for_sbp')->nullable(); // Телефон для СБП (для физлиц/самозанятых)
            $table->boolean('tax_check_required')->default(false); // Требуется ли чек из "Мой налог" (для самозанятых)
            $table->text('additional_info')->nullable(); // Дополнительная информация (например, паспортные данные, если нужно)
            $table->boolean('is_verified')->default(false); // Флаг верификации реквизитов (для админов)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisites');
    }
};