<?php

namespace App\Observers;

use App\Helpers\ErrorNotifier;
use Illuminate\Support\Arr;
use App\Helpers\Partners;
use App\Models\PartnerApplication;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Notifications\AppActiveNotification;
use App\Notifications\ApplicationAcceptedNotification;
use App\Notifications\ApplicationAcceptedToCompanyNotification;
use App\Notifications\ApplicationRejectedNotification;
use Illuminate\Support\Facades\Notification;

class PartnerApplicationObserver
{
    /**
     * Handle the PartnerApplication "updated" event.
     */
    public function updated(PartnerApplication $partnerApplication): void
    {
        // Проверяем, изменился ли статус
        if (!$partnerApplication->isDirty('status_id')) {
            return;
        }

        $oldStatus = $partnerApplication->getOriginal('status_id');
        $newStatus = $partnerApplication->status_id;

        // Отправляем уведомление ТОЛЬКО если статус стал равен 2
        if ($oldStatus !== 2 && $newStatus === 2) {
            $this->sendAppActiveNotification($partnerApplication);
        }

        // Блок для статуса 0
        if ($oldStatus !== 0 && $newStatus === 0) {
            $this->sendApplicationAcceptedNotification($partnerApplication);
            $this->sendApplicationAcceptedResponseNotification($partnerApplication);
        }

        // Новый блок: отправляем уведомление, если статус стал равен 3 (отклонено)
        if ($oldStatus !== 3 && $newStatus === 3) {
            $this->sendApplicationRejectedNotification($partnerApplication);
        }
    }

    /**
     * Отправляет уведомление AppActiveNotification
     */
    protected function sendAppActiveNotification(PartnerApplication $application): void
    {
        try {
            // Получаем пользователя, которому отправляем уведомление
            $user = $application->user;

            if (! $user) {
                ErrorNotifier::notify('Не найден пользователь для отправки уведомления');
                return;
            }

            Notification::send($user, new AppActiveNotification([
                'application_id' => $application->id,
                'cooperation_type' => $application->cooperationType?->name ?? 'не указан',
            ]));
        } catch (\Exception $e) {

            ErrorNotifier::notify('Ошибка при отправке AppActiveNotification');
        }
    }

    /**
     * Отправляет уведомление ApplicationAcceptedNotification
     */
    protected function sendApplicationAcceptedNotification(PartnerApplication $application): void
    {
        try {
            // Получаем пользователя, которому отправляем уведомление
            $user = $application->user;

            if (! $user) {
                ErrorNotifier::notify('Не найден пользователь для отправки уведомления о принятии заявки');
                return;
            }

            Notification::send($user, new ApplicationAcceptedNotification());
        } catch (\Exception $e) {

            ErrorNotifier::notify('Ошибка при отправке ApplicationAcceptedNotification');
        }
    }

    /**
     * Новый метод: Отправляет уведомление ApplicationRejectedNotification
     */
    protected function sendApplicationRejectedNotification(PartnerApplication $application): void
    {
        try {
            // Получаем пользователя, которому отправляем уведомление
            $user = $application->user;

            if (! $user) {
                ErrorNotifier::notify('Не найден пользователь для отправки уведомления об отклонении заявки');
                return;
            }

            Notification::send($user, new ApplicationRejectedNotification());

        } catch (\Exception $e) {

            ErrorNotifier::notify('Ошибка при отправке ApplicationRejectedNotification');
        }
    }

    /**
     * Отправляет уведомление ApplicationAcceptedToCompanyNotification для команды
     */
    protected function sendApplicationAcceptedResponseNotification(PartnerApplication $application): void
    {
        try {
            // Получаем настройки и email ответственного
            $globalSettings = Partners::getSettings('global');
            $email = Arr::get($globalSettings, 'responsible_partnersmail');

            if (!$email) {
                // Fallback на вложенный путь, если нужно
                $email = Arr::get($globalSettings, 'global.responsible_partnersmail');
            }

            if (!$email) {
                ErrorNotifier::notify('Почта компании из настроек не найдена');
                return;
            }

            // Отправляем на email компании (без notifiable-модели)
            Notification::route('mail', $email)
                ->notify(new ApplicationAcceptedToCompanyNotification($application));

        } catch (\Exception $e) {
            ErrorNotifier::notify('Ошибка при отправке уведомления о новой заявке в компанию');
        }
    }

    /**
     * Handle the PartnerApplication "created" event.
     */
    public function created(PartnerApplication $partnerApplication): void
    {
        // Отправляем уведомление, если статус при создании равен 0
        if ($partnerApplication->status_id === 0) {
            // Создателю
            $this->sendApplicationAcceptedNotification($partnerApplication);
            // В компанию
            $this->sendApplicationAcceptedResponseNotification($partnerApplication);
        }

        // Пока пусто — можно будет добавить логику позже (если нужно)
    }

    /**
     * Handle the PartnerApplication "deleted" event.
     */
    public function deleted(PartnerApplication $partnerApplication): void
    {
        // Пока не нужно
    }

    public function restored(PartnerApplication $partnerApplication): void {}
    public function forceDeleted(PartnerApplication $partnerApplication): void {}
}