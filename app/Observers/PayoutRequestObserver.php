<?php

namespace App\Observers;

use App\Helpers\ErrorNotifier;
use App\Models\PayoutRequest;
use Illuminate\Support\Arr;
use App\Helpers\Partners;
use Illuminate\Support\Facades\Log;
use App\Notifications\PayoutRequestCreatedNotification;
use App\Notifications\PayoutRequestNewToCompanyNotification;
use Illuminate\Support\Facades\Notification;

class PayoutRequestObserver
{
    /**
     * Handle the PayoutRequest "created" event.
     */
    public function created(PayoutRequest $payoutRequest): void
    {
        // Лог старта: событие created сработало
        Log::info("[PayoutRequestObserver] Created: Заявка на выплату {$payoutRequest->id} для пользователя {$payoutRequest->user_id} (сумма: {$payoutRequest->withdrawal_amount}) создана. Запуск уведомлений.");

        // Юзеру
        $this->sendPayoutRequestCreatedNotification($payoutRequest);
        // В компанию
        $this->sendPayoutRequestNewToCompanyNotification($payoutRequest);
    }

    /**
     * Отправляет уведомление PayoutRequestCreatedNotification юзеру
     */
    protected function sendPayoutRequestCreatedNotification(PayoutRequest $payoutRequest): void
    {
        try {
            $user = $payoutRequest->user;

            if (! $user) {
                ErrorNotifier::notify('Не найден пользователь для отправки уведомления о заявке на выплату');
                Log::error("[PayoutRequestObserver] Ошибка: User не найден для заявки {$payoutRequest->id}.");
                return;
            }

            Notification::send($user, new PayoutRequestCreatedNotification($payoutRequest));

            Log::info("[PayoutRequestObserver] Уведомление юзеру успешно отправлено для заявки {$payoutRequest->id}.");

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            ErrorNotifier::notify('Ошибка при отправке PayoutRequestCreatedNotification: ' . $errorMsg);
            Log::error("[PayoutRequestObserver] Исключение: $errorMsg. Stack: " . $e->getTraceAsString());
        }
    }

    /**
     * Отправляет уведомление PayoutRequestNewToCompanyNotification для команды
     */
    protected function sendPayoutRequestNewToCompanyNotification(PayoutRequest $payoutRequest): void
    {
        try {
            // Получаем настройки и email ответственного
            $globalSettings = Partners::getSettings('global');
            $email = Arr::get($globalSettings, 'responsible_partnersmail');

            if (!$email) {
                $email = Arr::get($globalSettings, 'global.responsible_partnersmail');
            }

            if (!$email) {
                ErrorNotifier::notify('Почта компании из настроек не найдена для уведомления о заявке на выплату');
                Log::error("[PayoutRequestObserver] Ошибка: Email компании не найден для заявки {$payoutRequest->id}.");
                return;
            }

            Log::info("[PayoutRequestObserver] Отправка на $email для заявки {$payoutRequest->id} юзера {$payoutRequest->user_id} (сумма: {$payoutRequest->withdrawal_amount}).");

            Notification::route('mail', $email)
                ->notify(new PayoutRequestNewToCompanyNotification($payoutRequest));

            Log::info("[PayoutRequestObserver] Уведомление компании успешно отправлено на $email для заявки {$payoutRequest->id}.");

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            ErrorNotifier::notify('Ошибка при отправке PayoutRequestNewToCompanyNotification: ' . $errorMsg);
            Log::error("[PayoutRequestObserver] Исключение: $errorMsg. Stack: " . $e->getTraceAsString());
        }
    }

    /**
     * Handle the PayoutRequest "updated" event.
     */
    public function updated(PayoutRequest $payoutRequest): void
    {
        // Пока пусто — можно добавить для approved/paid
    }

    /**
     * Handle the PayoutRequest "deleted" event.
     */
    public function deleted(PayoutRequest $payoutRequest): void
    {
        // Пока не нужно
    }

    public function restored(PayoutRequest $payoutRequest): void {}
    public function forceDeleted(PayoutRequest $payoutRequest): void {}
}