<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_access_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('access_level_id'); // ID из конфигурации
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_access_levels');
    }
};
