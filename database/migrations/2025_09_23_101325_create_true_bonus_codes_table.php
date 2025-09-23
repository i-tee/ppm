<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('true_bonus_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('joomla_user_id')->nullable();
            $table->unsignedBigInteger('bonus_code_id')->unique();
            $table->string('bonus_code_value')->unique();
            $table->decimal('bonus_code_cost', 8, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('true_bonus_codes');
    }
};