<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Lang;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    /**
     * Срок действия ссылки (в минутах)
     */
    public $expireMinutes = 60;

    /**
     * Токен для сброса
     */
    public $token;

    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->subject(__('notifications.emails.password_reset_subject'))
            ->greeting(__('notifications.others.hello') . ', ' . $notifiable->name)
            ->line(__('notifications.emails.password_reset_intro'))
            ->action(__('notifications.emails.password_reset_action'), $resetUrl)
            ->line(__('notifications.emails.password_reset_expires', [
                'count' => $this->expireMinutes
            ]))
            ->line(__('notifications.emails.password_reset_no_request'));
    }

    /**
     * Получить URL для сброса пароля
     */
    protected function resetUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'password.reset',
            Carbon::now()->addMinutes($this->expireMinutes),
            [
                'token' => $this->token,
                'email' => $notifiable->getEmailForVerification(),
            ]
        );
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'token' => $this->token,
        ];
    }
}