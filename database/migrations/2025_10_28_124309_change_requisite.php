<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requisites', function (Blueprint $table) {
            // === ДОБАВЛЯЕМ НОВЫЕ ПОЛЯ ===
            
            // Базовые поля
            $table->boolean('is_active')->default(true)->after('is_verified');
            $table->softDeletes();
            
            // Переименовываем существующие поля для единообразия
            $table->renameColumn('payment_account', 'bank_payment_account');
            $table->renameColumn('correspondent_account', 'bank_correspondent_account');
            $table->renameColumn('bik', 'bank_bik');
            
            // Перемещаем поля в соответствующие группы
            $table->renameColumn('inn', 'org_inn');
            $table->renameColumn('ogrn', 'org_ogrn');
            $table->renameColumn('kpp', 'org_kpp');
            $table->renameColumn('legal_address', 'org_legal_address');
            $table->renameColumn('postal_address', 'org_postal_address');
            $table->renameColumn('organization_name', 'org_full_name');
            
            // Добавляем недостающие поля для паспортных данных
            $table->string('passport_snils', 14)->nullable()->after('birth_place');
            
            // Добавляем недостающие поля для карточки организации
            $table->string('org_ogrnip', 15)->nullable()->after('org_ogrn');
            $table->string('org_tax_system')->nullable()->after('org_director_basis');
            
            // === УДАЛЯЕМ ЛИШНИЕ ПОЛЯ (которые дублируются или не нужны) ===
            $table->dropColumn([
                'snils',           // заменен на passport_snils
                'phone',           // заменен на org_phone
                'email',           // заменен на org_email  
                'website',         // заменен на org_website
                'tax_system',      // заменен на org_tax_system
                'notes',           // заменен на additional_info
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('requisites', function (Blueprint $table) {
            // Восстанавливаем исходную структуру при откате
            $table->dropColumn(['is_active', 'passport_snils', 'org_ogrnip', 'org_tax_system']);
            $table->dropSoftDeletes();
            
            // Возвращаем оригинальные названия полей
            $table->renameColumn('bank_payment_account', 'payment_account');
            $table->renameColumn('bank_correspondent_account', 'correspondent_account');
            $table->renameColumn('bank_bik', 'bik');
            $table->renameColumn('org_inn', 'inn');
            $table->renameColumn('org_ogrn', 'ogrn');
            $table->renameColumn('org_kpp', 'kpp');
            $table->renameColumn('org_legal_address', 'legal_address');
            $table->renameColumn('org_postal_address', 'postal_address');
            $table->renameColumn('org_full_name', 'organization_name');
            
            // Восстанавливаем удаленные поля
            $table->string('snils', 14)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('tax_system')->nullable();
            $table->text('notes')->nullable();
        });
    }
};