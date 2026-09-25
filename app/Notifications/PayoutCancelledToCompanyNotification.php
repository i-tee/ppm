<?php

namespace App\Notifications;

use App\Models\PayoutRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutCancelledToCompanyNotification extends Notification
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
        $userName = $this->payoutRequest->user?->name ?? 'Неизвестный пользователь';
        $amount = number_format($this->payoutRequest->withdrawal_amount, 2, ',', ' ');
        $requestId = $this->payoutRequest->id;
        $actorName = $this->payoutRequest->approver?->name ?? 'Неизвестный сотрудник';

        return (new MailMessage)
            ->subject(__('notifications.emails.payout_cancelled_to_company_subject'))
            ->greeting(__('notifications.emails.payout_cancelled_to_company_greeting'))
            ->line(__('notifications.emails.payout_cancelled_to_company_line1', [
                'name' => $userName,
                'request_id' => $requestId,
                'amount' => $amount,
            ]))
            ->line(__('notifications.emails.payout_cancelled_to_company_line2', [
                'actor' => $actorName,
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
