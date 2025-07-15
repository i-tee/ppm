<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('partner_applications', function (Blueprint $table) {
            $table->string('city')->nullable()->after('company_name');
            $table->json('links')->nullable()->after('city');
        });
    }

    public function down()
    {
        Schema::table('partner_applications', function (Blueprint $table) {
            $table->dropColumn(['city', 'links']);
        });
    }
};