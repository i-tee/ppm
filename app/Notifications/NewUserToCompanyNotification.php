<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class NewUserToCompanyNotification extends Notification
{
    use Queueable;

    protected User $user;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
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
        $userName = $this->user->name ?? 'Неизвестный пользователь';
        $userEmail = $this->user->email ?? 'Не указан';
        $registeredDate = Carbon::parse($this->user->created_at)->format('d.m.Y H:i'); // Формат даты, как в RU

        return (new MailMessage)
            ->subject(__('notifications.emails.new_user_subject'))
            ->greeting(__('notifications.emails.new_user_greeting'))
            ->line(__('notifications.emails.new_user_line1', [
                'name' => $userName,
                'email' => $userEmail,
                'date' => $registeredDate,
            ]))
            ->line(__('notifications.emails.new_user_line2'));
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