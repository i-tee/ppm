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
            // Паспортные данные
            $table->string('passport_series', 4)->nullable();
            $table->string('passport_number', 6)->nullable();
            $table->date('passport_issued_date')->nullable();
            $table->string('passport_issued_by')->nullable();
            $table->string('passport_issued_by_code', 10)->nullable();
            $table->string('passport_birth_place')->nullable();
            $table->text('passport_registration_address')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            
            // Банковские реквизиты (дополнительные)
            $table->string('bank_account_type')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_card_holder')->nullable();
            $table->string('bank_card_expiry', 7)->nullable();
            $table->string('bank_city')->nullable();
            $table->string('bank_inn', 12)->nullable();
            $table->string('bank_kpp', 9)->nullable();
            
            // Карточка организации
            $table->string('org_short_name')->nullable();
            $table->string('org_legal_form')->nullable();
            $table->string('org_okpo', 10)->nullable();
            $table->string('org_okato', 11)->nullable();
            $table->string('org_oktmo', 11)->nullable();
            $table->string('org_okfs', 2)->nullable();
            $table->string('org_okopf', 5)->nullable();
            $table->string('org_okved')->nullable();
            $table->string('org_director_name')->nullable();
            $table->string('org_director_position')->nullable();
            $table->string('org_director_basis')->nullable();
            $table->text('org_actual_address')->nullable();
            $table->string('org_phone', 20)->nullable();
            $table->string('org_email')->nullable();
            $table->string('org_website')->nullable();
            
            // Файлы и документы
            $table->string('doc_egrul')->nullable();
            $table->string('doc_inn')->nullable();
            $table->string('doc_ogrn')->nullable();
            $table->string('doc_ogrnip')->nullable();
            $table->string('doc_kpp')->nullable();
            $table->string('doc_charter')->nullable();
            $table->string('doc_director_appointment')->nullable();
            $table->string('doc_bank_account')->nullable();
            $table->string('doc_license')->nullable();
            $table->json('doc_other')->nullable();
            
            // Дополнительные поля
            $table->string('snils', 14)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('tax_system')->nullable();
            $table->text('notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requisites', function (Blueprint $table) {
            // Паспортные данные
            $table->dropColumn([
                'passport_series',
                'passport_number',
                'passport_issued_date',
                'passport_issued_by',
                'passport_issued_by_code',
                'passport_birth_place',
                'passport_registration_address',
                'birth_date',
                'birth_place',
                
                // Банковские реквизиты
                'bank_account_type',
                'bank_account_number',
                'bank_card_holder',
                'bank_card_expiry',
                'bank_city',
                'bank_inn',
                'bank_kpp',
                
                // Карточка организации
                'org_short_name',
                'org_legal_form',
                'org_okpo',
                'org_okato',
                'org_oktmo',
                'org_okfs',
                'org_okopf',
                'org_okved',
                'org_director_name',
                'org_director_position',
                'org_director_basis',
                'org_actual_address',
                'org_phone',
                'org_email',
                'org_website',
                
                // Файлы и документы
                'doc_egrul',
                'doc_inn',
                'doc_ogrn',
                'doc_ogrnip',
                'doc_kpp',
                'doc_charter',
                'doc_director_appointment',
                'doc_bank_account',
                'doc_license',
                'doc_other',
                
                // Дополнительные поля
                'snils',
                'phone',
                'email',
                'website',
                'tax_system',
                'notes',
            ]);
        });
    }
};