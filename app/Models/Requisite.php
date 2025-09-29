<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\Partners;

class Requisite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'partner_type_id',
        'full_name',
        'organization_name',
        'inn',
        'ogrn',
        'kpp',
        'legal_address',
        'postal_address',
        'bank_name',
        'bik',
        'correspondent_account',
        'payment_account',
        'card_number',
        'phone_for_sbp',
        'tax_check_required',
        'additional_info',
        'is_verified',
    ];

    /**
     * Связь с пользователем.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить тип партнера по ID (из хелпера Partners).
     * Это вспомогательный метод, данные берутся из Partners::getSettings('partner_types').
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
     * Валидация реквизитов в зависимости от типа партнера.
     * Можно вызвать перед сохранением.
     */
    public function validateByType()
    {
        $errors = [];

        switch ($this->partner_type_id) {
            case 1: // individual (физлицо)
                if (empty($this->full_name)) $errors[] = 'full_name required';
                if (empty($this->card_number) && empty($this->phone_for_sbp)) $errors[] = 'card_number or phone_for_sbp required';
                break;
            case 2: // self-employed (самозанятый)
                if (empty($this->full_name)) $errors[] = 'full_name required';
                if (empty($this->inn)) $errors[] = 'inn required';
                if (empty($this->card_number) && empty($this->phone_for_sbp)) $errors[] = 'card_number or phone_for_sbp required';
                $this->tax_check_required = true; // Автоматически устанавливаем
                break;
            case 3: // entrepreneur (ИП)
                if (empty($this->organization_name)) $errors[] = 'organization_name required';
                if (empty($this->inn)) $errors[] = 'inn required';
                if (empty($this->ogrn)) $errors[] = 'ogrn required';
                if (empty($this->payment_account)) $errors[] = 'payment_account required';
                if (empty($this->bik)) $errors[] = 'bik required';
                if (empty($this->bank_name)) $errors[] = 'bank_name required';
                break;
            case 4: // company (ООО)
                if (empty($this->organization_name)) $errors[] = 'organization_name required';
                if (empty($this->inn)) $errors[] = 'inn required';
                if (empty($this->ogrn)) $errors[] = 'ogrn required';
                if (empty($this->kpp)) $errors[] = 'kpp required';
                if (empty($this->payment_account)) $errors[] = 'payment_account required';
                if (empty($this->bik)) $errors[] = 'bik required';
                if (empty($this->bank_name)) $errors[] = 'bank_name required';
                break;
            default:
                $errors[] = 'Invalid partner_type_id';
        }

        if (!empty($errors)) {
            throw new \Exception(implode(', ', $errors));
        }

        return true;
    }

    // Пример CRUD-методов (статических для удобства, но лучше использовать в контроллере/репозитории)

    /**
     * Создать новые реквизиты.
     */
    public static function createRequisite(array $data)
    {
        $requisite = new self($data);
        $requisite->validateByType();
        $requisite->save();
        return $requisite;
    }

    /**
     * Обновить реквизиты.
     */
    public function updateRequisite(array $data)
    {
        $this->fill($data);
        $this->validateByType();
        $this->save();
        return $this;
    }

    /**
     * Удалить реквизиты.
     */
    public function deleteRequisite()
    {
        $this->delete();
    }

    /**
     * Получить реквизиты по user_id.
     */
    public static function getByUserId($userId)
    {
        return self::where('user_id', $userId)->first();
    }
};