<?php

namespace App\Observers;

use App\Helpers\ErrorNotifier;
use App\Models\User;
use Illuminate\Support\Arr;
use App\Helpers\Partners;
use Illuminate\Support\Facades\Log;
use App\Notifications\NewUserToCompanyNotification;
use Illuminate\Support\Facades\Notification;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Лог старта: событие created сработало
        // Log::info("[UserObserver] Created: Новый пользователь {$user->id} ({$user->email}) создан. Запуск уведомления в компанию.");

        // Отправляем уведомление в компанию о новой регистрации
        $this->sendNewUserToCompanyNotification($user);
    }

    /**
     * Отправляет уведомление NewUserToCompanyNotification для команды
     */
    protected function sendNewUserToCompanyNotification(User $user): void
    {
        try {
            // Лог: Получение настроек
            // Log::info("[UserObserver] Получение global settings для email.");

            // Получаем настройки и email ответственного
            $globalSettings = Partners::getSettings('global');
            // Log::info("[UserObserver] Global settings получены: " . json_encode($globalSettings)); // Для дебага — убери, если много данных

            $email = Arr::get($globalSettings, 'responsible_partnersmail');

            if (!$email) {
                // Fallback на вложенный путь, если нужно
                $email = Arr::get($globalSettings, 'global.responsible_partnersmail');
            }

            // Log::info("[UserObserver] Email из настроек: " . ($email ?? 'НЕ НАЙДЕН'));

            if (!$email) {
                ErrorNotifier::notify('Почта компании из настроек не найдена для уведомления о новом юзере');
                // Log::error("[UserObserver] Ошибка: Email компании не найден. Уведомление не отправлено для юзера {$user->email}.");
                return;
            }

            // Лог для дебага (убери, если не нужно)
            // Log::info("[UserObserver] Отправка на $email для юзера {$user->email}");

            // Отправляем на email компании (без notifiable-модели)
            Notification::route('mail', $email)
                ->notify(new NewUserToCompanyNotification($user));

            // Log::info("[UserObserver] Уведомление успешно отправлено на $email для юзера {$user->email}.");

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            ErrorNotifier::notify('Ошибка при отправке уведомления о новом юзере в компанию: ' . $errorMsg);
            // Log::error("[UserObserver] Исключение при отправке: $errorMsg. Stack: " . $e->getTraceAsString());
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Пока пусто — можно добавить логику позже (если нужно)
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        // Пока не нужно
    }

    public function restored(User $user): void {}
    public function forceDeleted(User $user): void {}
}
