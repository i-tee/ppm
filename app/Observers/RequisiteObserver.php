<?php

namespace App\Observers;

use App\Helpers\ErrorNotifier;
use App\Models\Requisite;
use Illuminate\Support\Arr;
use App\Helpers\Partners;
use Illuminate\Support\Facades\Log;
use App\Notifications\NewRequisiteNotification;
use App\Notifications\NewRequisiteToCompanyNotification;
use App\Notifications\RequisiteVerifiedNotification; // Новый импорт
use Illuminate\Support\Facades\Notification;

class RequisiteObserver
{
    /**
     * Handle the Requisite "created" event.
     */
    public function created(Requisite $requisite): void
    {
        // Лог старта: событие created сработало
        Log::info("[RequisiteObserver] Created: Новые реквизиты {$requisite->id} для пользователя {$requisite->user_id} созданы. Запуск уведомлений.");

        // Юзеру
        $this->sendNewRequisiteNotification($requisite);
        // В компанию
        $this->sendNewRequisiteToCompanyNotification($requisite);
    }

    /**
     * Handle the Requisite "updated" event.
     */
    public function updated(Requisite $requisite): void
    {
        // Новый блок: отправляем уведомление юзеру, если is_verified стал true
        if ($requisite->isDirty('is_verified') && $requisite->is_verified) {
            Log::info("[RequisiteObserver] Updated: Реквизиты {$requisite->id} верифицированы. Запуск уведомления юзеру.");
            $this->sendRequisiteVerifiedNotification($requisite);
        }
    }

    /**
     * Отправляет уведомление NewRequisiteNotification юзеру
     */
    protected function sendNewRequisiteNotification(Requisite $requisite): void
    {
        try {
            $user = $requisite->user;

            if (! $user) {
                ErrorNotifier::notify('Не найден пользователь для отправки уведомления о новых реквизитах');
                Log::error("[RequisiteObserver] Ошибка: User не найден для реквизита {$requisite->id}.");
                return;
            }

            Notification::send($user, new NewRequisiteNotification());

            Log::info("[RequisiteObserver] Уведомление юзеру успешно отправлено для реквизита {$requisite->id}.");

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            ErrorNotifier::notify('Ошибка при отправке NewRequisiteNotification: ' . $errorMsg);
            Log::error("[RequisiteObserver] Исключение: $errorMsg. Stack: " . $e->getTraceAsString());
        }
    }

    /**
     * Новый метод: Отправляет уведомление RequisiteVerifiedNotification юзеру
     */
    protected function sendRequisiteVerifiedNotification(Requisite $requisite): void
    {
        try {
            $user = $requisite->user;

            if (! $user) {
                ErrorNotifier::notify('Не найден пользователь для отправки уведомления о верифицированных реквизитах');
                Log::error("[RequisiteObserver] Ошибка: User не найден для верифицированного реквизита {$requisite->id}.");
                return;
            }

            Notification::send($user, new RequisiteVerifiedNotification());

            Log::info("[RequisiteObserver] Уведомление о верификации успешно отправлено юзеру для реквизита {$requisite->id}.");

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            ErrorNotifier::notify('Ошибка при отправке RequisiteVerifiedNotification: ' . $errorMsg);
            Log::error("[RequisiteObserver] Исключение: $errorMsg. Stack: " . $e->getTraceAsString());
        }
    }

    /**
     * Отправляет уведомление NewRequisiteToCompanyNotification для команды
     */
    protected function sendNewRequisiteToCompanyNotification(Requisite $requisite): void
    {
        try {
            // Получаем настройки и email ответственного
            $globalSettings = Partners::getSettings('global');
            $email = Arr::get($globalSettings, 'responsible_partnersmail');

            if (!$email) {
                $email = Arr::get($globalSettings, 'global.responsible_partnersmail');
            }

            if (!$email) {
                ErrorNotifier::notify('Почта компании из настроек не найдена для уведомления о новых реквизитах');
                Log::error("[RequisiteObserver] Ошибка: Email компании не найден для реквизита {$requisite->id}.");
                return;
            }

            Log::info("[RequisiteObserver] Отправка на $email для реквизита {$requisite->id} юзера {$requisite->user_id}.");

            Notification::route('mail', $email)
                ->notify(new NewRequisiteToCompanyNotification($requisite));

            Log::info("[RequisiteObserver] Уведомление компании успешно отправлено на $email для реквизита {$requisite->id}.");

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            ErrorNotifier::notify('Ошибка при отправке NewRequisiteToCompanyNotification: ' . $errorMsg);
            Log::error("[RequisiteObserver] Исключение: $errorMsg. Stack: " . $e->getTraceAsString());
        }
    }

    /**
     * Handle the Requisite "deleted" event.
     */
    public function deleted(Requisite $requisite): void
    {
        // Пока не нужно
    }

    public function restored(Requisite $requisite): void {}
    public function forceDeleted(Requisite $requisite): void {}
}