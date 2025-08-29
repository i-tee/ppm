<?php

namespace App\Observers;

use App\Helpers\ErrorNotifier;
use App\Models\PartnerApplication;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Notifications\AppActiveNotification;
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
     * Handle the PartnerApplication "created" event.
     */
    public function created(PartnerApplication $partnerApplication): void
    {
        // Пока пусто — можно будет добавить логику позже
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
