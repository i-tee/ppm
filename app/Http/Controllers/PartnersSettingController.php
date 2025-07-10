<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PartnersSettingController extends Controller
{
    public function index()
    {
        // Получаем все настройки
        $settings = \App\Helpers\Partners::getSettings();

        // Возвращаем данные (например, в JSON)
        return response()->json($settings);
    }
}