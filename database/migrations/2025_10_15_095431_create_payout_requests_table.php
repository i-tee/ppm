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
        Schema::create('payout_requests', function (Blueprint $table) {
            $table->id();

            // Связи (просто ID-поля с индексами, без FK)
            $table->unsignedBigInteger('user_id')->index(); // ID агента-инициатора
            $table->unsignedBigInteger('approver_id')->nullable()->index(); // ID одобряющего
            $table->unsignedBigInteger('requisite_id')->index(); // ID реквизитов

            // Суммы и комиссия
            $table->decimal('withdrawal_amount', 10, 2);
            $table->decimal('received_amount', 10, 2)->nullable();
            $table->decimal('commission_percentage', 5, 2)->default(0);
            $table->decimal('commission_amount', 10, 2)->nullable();

            // Статус и активность
            $table->tinyInteger('status')->default(0)->comment('0: создана, 1: одобрена, 2: выплачена');
            $table->boolean('is_active')->default(true);

            // Дополнительно
            $table->text('note')->nullable();
            $table->string('proof_link', 500)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payout_requests');
    }
};