<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('partner_applications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('full_name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->unsignedInteger('cooperation_type_id');
            $table->unsignedInteger('partner_type_id')->nullable();
            $table->unsignedTinyInteger('status_id')->default(0); // 0 — Новая, 1 — В обработке, 2 — Принята, 3 — Отклонена

            $table->string('company_name')->nullable();
            $table->text('experience')->nullable();
            $table->text('comment')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_applications');
    }
};
