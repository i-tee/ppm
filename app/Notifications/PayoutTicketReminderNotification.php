<?php

namespace App\Notifications;

use App\Models\PayoutRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class PayoutTicketReminderNotification extends Notification
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
        $amount = number_format($this->payoutRequest->received_amount, 2, ',', ' ');
        $requestId = $this->payoutRequest->id;
        $date = Carbon::parse($this->payoutRequest->updated_at)->format('d.m.Y');
        $proofLink = $this->payoutRequest->proof_link ?? 'Не указана'; // Текст-url (кликабельно автоматически)
        $dashboardUrl = config('app.url'); // Кнопка на заявку в кабинете (измени если список: '/dashboard/payouts')

        return (new MailMessage)
            ->subject(__('notifications.emails.payout_ticket_reminder_subject'))
            ->greeting(__('notifications.others.hello') . ', ' . $notifiable->name)
            ->line(__('notifications.emails.payout_ticket_reminder_line1', [
                'request_id' => $requestId,
                'amount' => $amount,
                'date' => $date,
            ]))
            ->line(__('notifications.emails.payout_ticket_reminder_line2', [
                'proof_link' => $proofLink,
            ])) // proof_link как текст-url (кликабельно в почтовиках)
            ->line(__('notifications.emails.payout_ticket_reminder_line3'))
            ->line(__('notifications.emails.payout_ticket_reminder_line4'))
            ->action(__('notifications.emails.payout_ticket_reminder_button_dashboard'), $dashboardUrl); // Единственная кнопка на кабинет
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [];
    }
}