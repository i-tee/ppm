<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ErrorNotifier
{
    /**
     * Отправляет email администратору с деталями ошибки.
     * Файл и строка определяются автоматически.
     */
    public static function notify(string $message = 'пусто', array $context = [])
    {
        $adminEmail = Config::get('app.admin_error_email');

        if (! $adminEmail) {
            Log::warning('ADMIN_ERROR_EMAIL не задан в .env');
            return;
        }

        // Автоматически определяем, откуда вызван notify
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $trace[1] ?? null; // [0] — это сам notify, [1] — кто его вызвал

        $file = $caller['file'] ?? 'unknown';
        $line = $caller['line'] ?? 'unknown';

        $subject = '[ОШИБКА] ' . config('app.name') . ' — ' . now()->format('d.m.Y H:i');
        $body = self::buildBody($message, $file, $line, $context);

        try {
            Mail::raw($body, function ($mail) use ($adminEmail, $subject) {
                $mail->to($adminEmail)
                     ->subject($subject)
                     ->from(config('mail.from.address'), config('app.name') . ' (Ошибка)');
            });

            Log::info('Ошибка отправлена на email администратора', [
                'admin_email' => $adminEmail,
                'file' => $file,
                'line' => $line,
            ]);
        } catch (\Exception $e) {
            Log::error('Не удалось отправить email с ошибкой', [
                'original_error' => $e->getMessage(),
                'failed_notification' => compact('message', 'file', 'line', 'context'),
            ]);
        }
    }

    /**
     * Формирует тело письма
     */
    protected static function buildBody(string $message, string $file, $line, array $context): string
    {
        $appName = config('app.name');
        $url = config('app.url');
        $time = now()->format('d.m.Y H:i:s');

        $body = "🚨 СИСТЕМНАЯ ОШИБКА\n";
        $body .= "Проект: $appName\n";
        $body .= "Сервер: $url\n";
        $body .= "Время: $time\n\n";

        $body .= "Сообщение: $message\n";
        $body .= "Файл: $file\n";
        $body .= "Строка: $line\n";

        if (!empty($context)) {
            $body .= "\nКонтекст:\n";
            foreach ($context as $key => $value) {
                $body .= "  $key: " . json_encode($value, JSON_UNESCAPED_UNICODE) . "\n";
            }
        }

        return $body;
    }
}