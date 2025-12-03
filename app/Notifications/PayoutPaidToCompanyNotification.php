<?php

namespace App\Notifications;

use App\Models\PayoutRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class PayoutPaidToCompanyNotification extends Notification
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
        $received_amount = number_format($this->payoutRequest->received_amount, 2, ',', ' ');
        $requestId = $this->payoutRequest->id;
        $date = Carbon::parse($this->payoutRequest->updated_at)->format('d.m.Y H:i');
        $proofLink = $this->payoutRequest->proof_link ?? 'Не указана';

        return (new MailMessage)
            ->subject(__('notifications.emails.payout_paid_to_company_subject'))
            ->greeting(__('notifications.emails.payout_paid_to_company_greeting'))
            ->line(__('notifications.emails.payout_paid_to_company_line1', [
                'name' => $userName,
                'request_id' => $requestId,
                'amount' => $amount,
                'received_amount' => $received_amount,
            ]))
            ->line(__('notifications.emails.payout_paid_to_company_line2', [
                'date' => $date,
                'proof_link' => $proofLink,
            ]))
            ->line(__('notifications.emails.payout_paid_to_company_line3'));
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
