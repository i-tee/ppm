<?php

namespace App\Notifications;

use App\Models\PayoutRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PayoutTickedUploadToCompanyNotification extends Notification
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
        $amount = number_format($this->payoutRequest->received_amount, 2, ',', ' ');
        $requestId = $this->payoutRequest->id;
        $ticketPath = $this->payoutRequest->ticket_proof ?? null;
        $ticketUrl = $ticketPath ? url(Storage::url($ticketPath)) : 'Файл не прикреплён';

        return (new MailMessage)
            ->subject(__('notifications.emails.payout_ticket_upload_subject'))
            ->greeting(__('notifications.emails.payout_ticket_upload_greeting'))
            ->line(__('notifications.emails.payout_ticket_upload_line1', [
                'name' => $userName,
                'request_id' => $requestId,
                'amount' => $amount,
            ]))
            ->line(__('notifications.emails.payout_ticket_upload_line2', [
                'ticket_url' => $ticketUrl,
            ]))
            ->line(__('notifications.emails.payout_ticket_upload_line3'));
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
