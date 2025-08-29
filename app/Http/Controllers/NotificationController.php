<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    public function send(Request $request)
    {

        // return response()->json(['message' => 'Notification xxx']);

        $request->validate([
            'notification' => 'required|string',
            'user_id' => 'required|exists:users,id',
            // можно добавить 'data' => 'array' для кастомных переменных
        ]);

        $user = User::find($request->user_id);
        $notificationClass = $this->resolveNotification($request->notification);

        if (! $notificationClass) {
            return response()->json(['error' => 'Notification not found'], 404);
        }

        Log::info('itee - Sending email to: ' . $user->email);
        Log::info('itee - data: ', $request->data ?? []);

        Notification::send($user, new $notificationClass($request->data ?? []));

        Log::info('itee - Notification send end.');

        return response()->json(['message' => 'Notification send end']);
    }

    protected function resolveNotification(string $name)
    {
        $namespace = 'App\\Notifications\\';
        $className = Str::studly($name);

        $fullClass = $namespace . $className;

        if (class_exists($fullClass)) {
            return $fullClass;
        }

        return null;
    }
}