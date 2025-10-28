<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Helpers\Partners;

class Requisite extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        // === БАЗОВЫЕ ПОЛЯ ===
        'user_id',                  // ID пользователя в системе
        'partner_type_id',          // Тип партнера: 1-физлицо, 2-самозанятый, 3-ИП, 4-компания
        'full_name',                // ФИО физического лица или директора компании
        'is_verified',              // Статус верификации реквизитов администратором
        'is_active',                // Актуальность реквизитов (вместо удаления)
        'additional_info',          // Дополнительная информация (произвольные заметки)
        'tax_check_required',       // Требуется ли чек из приложения "Мой налог" (для самозанятых)
        
        // === ПАСПОРТНЫЕ ДАННЫЕ ===
        'passport_series',          // Серия паспорта (4 цифры)
        'passport_number',          // Номер паспорта (6 цифр)
        'passport_issued_date',     // Дата выдачи паспорта
        'passport_issued_by',       // Кем выдан паспорт (полное название органа)
        'passport_issued_by_code',  // Код подразделения (формат 000-000)
        'passport_birth_place',     // Место рождения (как в паспорте)
        'passport_registration_address', // Адрес регистрации (прописка)
        'birth_date',               // Дата рождения
        'birth_place',              // Место рождения
        'passport_snils',           // СНИЛС - страховой номер индивидуального лицевого счета
        
        // === БАНКОВСКИЕ РЕКВИЗИТЫ ===
        'bank_name',                // Наименование банка
        'bank_bik',                 // БИК - банковский идентификационный код
        'bank_correspondent_account', // Корреспондентский счет банка
        'bank_payment_account',     // Расчетный счет организации/ИП
        'bank_card_number',         // Номер банковской карты (для физлиц)
        'bank_phone_for_sbp',       // Номер телефона для Системы Быстрых Платежей
        'bank_account_type',        // Тип счета: settlement-расчетный, card-карточный, personal-лицевой
        'bank_account_number',      // Номер счета (расчетного, лицевого)
        'bank_card_holder',         // Имя держателя карты (как на карте)
        'bank_card_expiry',         // Срок действия карты (формат MM/YY)
        'bank_city',                // Город нахождения банка
        'bank_inn',                 // ИНН банка
        'bank_kpp',                 // КПП банка
        
        // === КАРТОЧКА ОРГАНИЗАЦИИ ===
        'org_full_name',            // Полное наименование организации
        'org_short_name',           // Сокращенное наименование организации
        'org_legal_form',           // Организационно-правовая форма: ООО, АО, ИП и т.д.
        'org_inn',                  // ИНН организации
        'org_ogrn',                 // ОГРН - основной государственный регистрационный номер (для юрлиц)
        'org_ogrnip',               // ОГРНИП - основной государственный регистрационный номер ИП
        'org_kpp',                  // КПП - код причины постановки на учет (только для юрлиц)
        'org_legal_address',        // Юридический адрес (место государственной регистрации)
        'org_postal_address',       // Почтовый адрес (для корреспонденции)
        'org_actual_address',       // Фактический адрес местонахождения
        'org_phone',                // Телефон организации
        'org_email',                // Email организации
        'org_website',              // Сайт организации
        'org_okpo',                 // ОКПО - общероссийский классификатор предприятий
        'org_okato',                // ОКАТО - классификатор объектов административно-территориального деления
        'org_oktmo',                // ОКТМО - классификатор территорий муниципальных образований
        'org_okfs',                 // ОКФС - классификатор форм собственности
        'org_okopf',                // ОКОПФ - классификатор организационно-правовых форм
        'org_okved',                // ОКВЭД - классификатор видов экономической деятельности
        'org_director_name',        // ФИО руководителя организации
        'org_director_position',    // Должность руководителя
        'org_director_basis',       // Основание полномочий (Устав, Доверенность)
        'org_tax_system',           // Система налогообложения: ОСН, УСН, ЕНВД, Патент
        
        // === ДОКУМЕНТЫ ===
        'doc_egrul',                // Выписка из ЕГРЮЛ/ЕГРИП
        'doc_inn',                  // Свидетельство ИНН
        'doc_ogrn',                 // Свидетельство ОГРН (для юрлиц)
        'doc_ogrnip',               // Свидетельство ОГРНИП (для ИП)
        'doc_kpp',                  // Свидетельство КПП
        'doc_charter',              // Устав организации
        'doc_director_appointment', // Приказ о назначении директора
        'doc_bank_account',         // Выписка из банка о открытии счета
        'doc_license',              // Лицензии (если деятельность лицензируется)
        'doc_other',                // Прочие документы (JSON массив)
    ];

    protected $casts = [
        // Базовые
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'tax_check_required' => 'boolean',
        
        // Даты
        'passport_issued_date' => 'date',
        'birth_date' => 'date',
        
        // JSON
        'doc_other' => 'array',
        
        // Тимстемпы
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Scope для активных реквизитов
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope для верифицированных реквизитов
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope по типу партнера
     */
    public function scopeByPartnerType($query, $partnerTypeId)
    {
        return $query->where('partner_type_id', $partnerTypeId);
    }

    /**
     * Scope для поиска по ИНН (организации или банка)
     */
    public function scopeByInn($query, $inn)
    {
        return $query->where('org_inn', $inn)->orWhere('bank_inn', $inn);
    }

    /**
     * Связь с пользователем
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить тип партнера по ID
     */
    public function getPartnerTypeName()
    {
        $partnerTypes = Partners::getSettings('partner_types');
        foreach ($partnerTypes as $type) {
            if ($type['id'] === $this->partner_type_id) {
                return $type['name'];
            }
        }
        return 'unknown';
    }

    /**
     * Аксессор: полные паспортные данные
     */
    public function getPassportFullAttribute()
    {
        if ($this->passport_series && $this->passport_number) {
            return "{$this->passport_series} {$this->passport_number}";
        }
        return null;
    }

    /**
     * Аксессор: полное наименование организации с ОПФ
     */
    public function getOrgFullNameWithLegalFormAttribute()
    {
        if (!$this->org_full_name) return null;
        
        if ($this->org_legal_form && !str_starts_with($this->org_full_name, $this->org_legal_form)) {
            return "{$this->org_legal_form} «{$this->org_full_name}»";
        }
        
        return $this->org_full_name;
    }

    /**
     * Аксессор: основные банковские реквизиты
     */
    public function getBankSummaryAttribute()
    {
        $details = [];
        if ($this->bank_name) $details[] = $this->bank_name;
        if ($this->bank_bik) $details[] = "БИК: {$this->bank_bik}";
        if ($this->bank_payment_account) $details[] = "Счет: {$this->bank_payment_account}";
        if ($this->bank_account_number) $details[] = "Счет: {$this->bank_account_number}";
        
        return implode(', ', $details);
    }

    /**
     * Аксессор: информация о директоре
     */
    public function getDirectorInfoAttribute()
    {
        $parts = [];
        if ($this->org_director_name) $parts[] = $this->org_director_name;
        if ($this->org_director_position) $parts[] = $this->org_director_position;
        if ($this->org_director_basis) $parts[] = "на основании {$this->org_director_basis}";
        
        return implode(', ', $parts);
    }

    /**
     * Проверить, заполнены ли паспортные данные
     */
    public function getHasPassportDataAttribute()
    {
        return !empty($this->passport_series) && !empty($this->passport_number);
    }

    /**
     * Проверить, заполнены ли банковские реквизиты
     */
    public function getHasBankDetailsAttribute()
    {
        return !empty($this->bank_name) && 
               (!empty($this->bank_payment_account) || !empty($this->bank_account_number) || !empty($this->bank_card_number));
    }

    /**
     * Проверить, заполнены ли данные организации
     */
    public function getHasOrgDataAttribute()
    {
        return !empty($this->org_full_name) && !empty($this->org_inn);
    }

    /**
     * Деактивировать реквизиты (мягкое удаление)
     */
    public function deactivate()
    {
        $this->is_active = false;
        return $this->save();
    }

    /**
     * Активировать реквизиты
     */
    public function activate()
    {
        $this->is_active = true;
        return $this->save();
    }

    /**
     * Добавить дополнительный документ
     */
    public function addDocument($name, $url, $uploadedAt = null)
    {
        $documents = $this->doc_other ?? [];
        $documents[] = [
            'name' => $name,
            'url' => $url,
            'uploaded_at' => $uploadedAt ?? now()->toDateTimeString(),
        ];
        
        $this->doc_other = $documents;
        return $this;
    }

    /**
     * Получить реквизиты по user_id (только активные)
     */
    public static function getActiveByUserId($userId)
    {
        return self::where('user_id', $userId)->active()->first();
    }

    /**
     * Получить все реквизиты пользователя (включая неактивные)
     */
    public static function getAllByUserId($userId)
    {
        return self::where('user_id', $userId)->get();
    }

    // === CRUD-МЕТОДЫ ДЛЯ ОБРАТНОЙ СОВМЕСТИМОСТИ ===

    /**
     * Создать новые реквизиты
     */
    public static function createRequisite(array $data)
    {
        $requisite = new self($data);
        $requisite->save();
        return $requisite;
    }

    /**
     * Обновить реквизиты
     */
    public function updateRequisite(array $data)
    {
        $this->fill($data);
        $this->save();
        return $this;
    }

    /**
     * Удалить реквизиты (деактивировать)
     */
    public function deleteRequisite()
    {
        return $this->deactivate();
    }

    /**
     * Получить реквизиты по ID пользователя (активные)
     */
    public static function getByUserId($userId)
    {
        return self::getActiveByUserId($userId);
    }
}