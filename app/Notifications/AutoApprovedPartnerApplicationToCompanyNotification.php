<?php

namespace App\Notifications;

use App\Models\PartnerApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AutoApprovedPartnerApplicationToCompanyNotification extends Notification
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
        $userName = $this->application->full_name ?? __('notifications.emails.unknown_user'); // Ключ: 'Неизвестный пользователь'
        $userEmail = $this->application->email ?? __('notifications.emails.email_not_set'); // Ключ: 'Не указан'
        $appId = $this->application->id;

        return (new MailMessage)
            ->subject(__('notifications.emails.auto_approved_application_subject'))
            ->greeting(__('notifications.emails.auto_approved_application_greeting'))
            ->line(__('notifications.emails.auto_approved_application_line1', [
                'name' => $userName,
                'email' => $userEmail,
                'id' => $appId,
            ]))
            ->line(__('notifications.emails.auto_approved_application_line2', [
                'cooperation_type' => __('notifications.emails.joomla_cooperation_type'),
                'partner_type' => __('notifications.emails.joomla_partner_type')
            ]));
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
