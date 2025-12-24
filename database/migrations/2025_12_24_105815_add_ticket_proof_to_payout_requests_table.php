<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('payout_requests', function (Blueprint $table) {
            $table->string('ticket_proof')->nullable(); // путь к файлу чека пользователя
        });
    }

    public function down()
    {
        Schema::table('payout_requests', function (Blueprint $table) {
            $table->dropColumn('ticket_proof');
        });
    }
};
