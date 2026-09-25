<?php

namespace App\Notifications;

use App\Models\PayoutRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutCancelledNotification extends Notification
{
    use Queueable;

    protected PayoutRequest $payoutRequest;
    protected string $reason;

    public function __construct(PayoutRequest $payoutRequest, string $reason)
    {
        $this->payoutRequest = $payoutRequest;
        $this->reason = $reason;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $amount = number_format($this->payoutRequest->withdrawal_amount, 2, ',', ' ');

        return (new MailMessage)
            ->subject(__('notifications.emails.payout_cancelled_subject'))
            ->greeting(__('notifications.others.hello') . ', ' . $notifiable->name)
            ->line(__('notifications.emails.payout_cancelled_line1', [
                'amount' => $amount,
            ]))
            ->line(__('notifications.emails.payout_cancelled_line2', [
                'reason' => $this->reason,
            ]));
    }

    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
