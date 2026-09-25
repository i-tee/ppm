<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('partner_applications', function (Blueprint $table) {
            $table->string('last_name')->nullable()->after('full_name');
            $table->string('first_name')->nullable()->after('last_name');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('specialty', 255)->nullable()->after('experience');
            $table->unsignedSmallInteger('experience_years')->nullable()->after('specialty');
        });
    }

    public function down()
    {
        Schema::table('partner_applications', function (Blueprint $table) {
            $table->dropColumn(['last_name', 'first_name', 'middle_name', 'specialty', 'experience_years']);
        });
    }
};
