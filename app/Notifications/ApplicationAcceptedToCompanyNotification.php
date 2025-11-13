<?php

namespace App\Notifications;

use App\Models\PartnerApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationAcceptedToCompanyNotification extends Notification
{
    use Queueable;

    protected PartnerApplication $application;

    /**
     * Create a new notification instance.
     */
    public function __construct(PartnerApplication $application)
    {
        $this->application = $application;
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
        $userName = $this->application->user?->name ?? 'Неизвестный пользователь';
        $appId = $this->application->id;

        return (new MailMessage)
            ->subject(__('notifications.emails.application_new_subject'))
            ->greeting(__('notifications.emails.application_new_greeting'))
            ->line(__('notifications.emails.application_new_line1', [
                'name' => $userName,
                'id' => $appId,
            ]))
            ->line(__('notifications.emails.application_new_line3'));
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
