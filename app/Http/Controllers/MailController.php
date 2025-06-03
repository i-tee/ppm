<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeEmail;

class MailController extends Controller
{
    public function sendWelcomeEmail()
    {
        $recipientEmail = 'tt7x62@yandex.ru'; // замени на нужный адрес

        Mail::to($recipientEmail)->send(new WelcomeEmail());

        return response()->json(['message' => 'Письмо отправлено']);
    }
}
