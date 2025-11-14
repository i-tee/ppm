<?php

namespace App\Notifications;

use App\Models\JoomlaCoupon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class NewBonusCouponToCompanyNotification extends Notification
{
    use Queueable;

    protected JoomlaCoupon $coupon;

    /**
     * Create a new notification instance.
     */
    public function __construct(JoomlaCoupon $coupon)
    {
        $this->coupon = $coupon;
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
        $code = $this->coupon->coupon_code ?? 'Не указан';
        $value = (float) ($this->coupon->coupon_value ?? 0);
        $expire = $this->coupon->coupon_expire_date ? Carbon::parse($this->coupon->coupon_expire_date)->format('d.m.Y') : 'Не указана';

        return (new MailMessage)
            ->subject(__('notifications.emails.coupon_bonus_new_subject'))
            ->greeting(__('notifications.emails.coupon_bonus_new_greeting'))
            ->line(__('notifications.emails.coupon_bonus_new_line1', [
                'code' => $code,
                'value' => $value,
            ]))
            ->line(__('notifications.emails.coupon_bonus_new_line2', [
                'expire' => $expire,
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