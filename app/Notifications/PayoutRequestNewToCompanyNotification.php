<?php

namespace App\Notifications;

use App\Models\PayoutRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutRequestNewToCompanyNotification extends Notification
{
    use Queueable;

    protected PayoutRequest $payoutRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(PayoutRequest $payoutRequest)
    {
        $this->payoutRequest = $payoutRequest;
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
        $userName = $this->payoutRequest->user?->name ?? 'Неизвестный пользователь';
        $amount = number_format($this->payoutRequest->withdrawal_amount, 2, ',', ' ');

        return (new MailMessage)
            ->subject(__('notifications.emails.payout_new_subject'))
            ->greeting(__('notifications.emails.payout_new_greeting'))
            ->line(__('notifications.emails.payout_new_line1', [
                'name' => $userName,
                'amount' => $amount
            ]))
            ->line(__('notifications.emails.payout_new_line2'));
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