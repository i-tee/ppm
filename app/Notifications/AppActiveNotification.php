<?php

// App/Notifications/AppActiveNotification.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class AppActiveNotification extends Notification
{

    use Queueable;

    public $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {

        Log::info('itee|: data - ' . json_encode($this->data));
        Log::info('itee|: notifiable - ' . json_encode($notifiable));

        $appName = config('app.name');
        $greeting = __('notifications.others.hello') . ', ' . $notifiable->name . '!';
        $subject = __('notifications.emails.application_approved_subject');
        $line1 = __('notifications.emails.application_approved_line1');
        $line2 = __('notifications.emails.application_approved_line2');
        $actionText = __('notifications.emails.go_to_dashboard');
        $salutation = __('notifications.emails.regards') . ', ' . __('notifications.emails.team') . ' ' . $appName;

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($line1)
            ->line($line2)
            ->action($actionText, url('/dashboard'))
            ->salutation($salutation);
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'Ваша заявка принята',
            'link' => '/dashboard',
        ];
    }
}
