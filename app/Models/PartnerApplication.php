<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\Partners;
use App\Notifications\AutoApprovedPartnerApplicationToCompanyNotification;
use App\Helpers\ErrorNotifier;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Arr;

class PartnerApplication extends Model
{
    protected $fillable = [
        'user_id',
        'responsible_user_id',
        'full_name',
        'phone',
        'email',
        'cooperation_type_id',
        'partner_type_id',
        'status_id',
        'company_name',
        'experience',
        'comment',
        'city',
        'links'
    ];

    protected $appends = ['status_name'];

    protected $casts = [
        'links' => 'array', // Автоматическое преобразование JSON ↔ array
    ];

    // Автор заявки
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Ответственный
    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    // Тип сотрудничества из хелпера
    public function getCooperationTypeAttribute(): ?array
    {
        $settings = \App\Helpers\Partners::getSettings();
        return collect($settings['cooperation_types'])->firstWhere('id', $this->cooperation_type_id);
    }

    // Тип партнёра из хелпера
    public function getPartnerTypeAttribute(): ?array
    {
        $settings = \App\Helpers\Partners::getSettings();
        return collect($settings['partner_types'])->firstWhere('id', $this->partner_type_id);
    }

    public function getStatusNameAttribute(): string
    {
        return match ($this->status_id) {
            0 => 'new',
            1 => 'in_progress',
            2 => 'accepted',
            3 => 'rejected',
            9 => 'blocked',
            default => 'unknown',
        };
    }

    /**
     * Создаёт новую заявку партнёра для пользователя.
     * Заполняет только указанные поля, остальные остаются пустыми.
     * Привязывает заявку к пользователю (user_id).
     * 
     * @param User $user Пользователь из Laravel (созданный или существующий)
     * @return PartnerApplication Созданная заявка
     */
    public static function createApprovedDefaultPartnerApplication(User $user): PartnerApplication
    {
        // Шаг 1: Подготавливаем данные для заявки
        // Заполняем только требуемые поля, остальные — null или дефолтные
        $applicationData = [
            'user_id' => $user->id, // Привязка к пользователю из Laravel
            'full_name' => $user->name, // Имя пользователя
            'email' => $user->email, // Email пользователя
            'cooperation_type_id' => 2, // Фиксированное значение
            'partner_type_id' => 1, // Фиксированное значение
            'status_id' => 2, // Фиксированное значение (одобрено)
            'phone' => Partners::getSettings('global.responsible_phone'), // Телефон компании Avicenna
            'comment' => 'AutoFromOld',
            // Остальные поля остаются пустыми (null)
            'responsible_user_id' => null,
            'company_name' => null,
            'experience' => null,
            'city' => null,
            'links' => null, // Массив будет null
        ];

        // Шаг 2: Создаём запись в дефолтной БД Laravel (не Joomla)
        // Используем create() для массового заполнения
        $application = self::create($applicationData);

        // Шаг 3: Логируем создание для отладки
        // \Log::info("[PartnerApplication] Автоматическая одобренная заявка создана для юзера {$user->id} (ID заявки: {$application->id}).");

        // Шаг 4: Отправляем уведомление в компанию
        static::sendAutoApprovedNotification($application);

        // Шаг 5: Возвращаем созданную заявку для дальнейшего использования
        return $application;
    }

    /**
     * Отправляет уведомление о автоматически одобренной заявке в компанию
     */
    protected static function sendAutoApprovedNotification(PartnerApplication $application): void
    {
        try {
            // Получаем email из настроек
            $globalSettings = Partners::getSettings('global');
            $email = Arr::get($globalSettings, 'responsible_partnersmail');

            if (!$email) {
                $email = Arr::get($globalSettings, 'global.responsible_partnersmail');
            }

            if (!$email) {
                ErrorNotifier::notify('Почта компании не найдена для уведомления об авто-одобренной заявке');
                Log::error("[PartnerApplication] Email не найден для заявки {$application->id}.");
                return;
            }

            Log::info("[PartnerApplication] Отправка уведомления об авто-одобренной заявке на $email (ID: {$application->id}).");

            Notification::route('mail', $email)
                ->notify(new AutoApprovedPartnerApplicationToCompanyNotification($application));

            Log::info("[PartnerApplication] Уведомление об авто-одобренной заявке отправлено на $email.");
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            ErrorNotifier::notify('Ошибка отправки уведомления об авто-одобренной заявке: ' . $errorMsg);
            Log::error("[PartnerApplication] Исключение: $errorMsg. Stack: " . $e->getTraceAsString());
        }
    }
}
