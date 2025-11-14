<?php

namespace App\Notifications;

use App\Models\Requisite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewRequisiteToCompanyNotification extends Notification
{
    use Queueable;

    protected Requisite $requisite;

    /**
     * Create a new notification instance.
     */
    public function __construct(Requisite $requisite)
    {
        $this->requisite = $requisite;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $userName = $this->requisite->user?->name ?? 'Неизвестный пользователь';
        $requisiteId = $this->requisite->id;
        $partnerType = $this->requisite->partnerType?->name ?? 'Не указан'; // Предполагаю relation partner_type
        $inn = $this->requisite->inn ?? 'Не указан';

        return (new MailMessage)
            ->subject(__('notifications.emails.requisite_new_subject'))
            ->greeting(__('notifications.emails.requisite_new_greeting'))
            ->line(__('notifications.emails.requisite_new_line1', [
                'name' => $userName,
                'id' => $requisiteId,
            ]))
            ->line(__('notifications.emails.requisite_new_line3'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}