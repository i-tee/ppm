<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RequisitesSettingController extends Controller
{
    public function index()
    {
        // Получаем все настройки
        $settings = \App\Helpers\Requisites::getFields();

        // Возвращаем данные (например, в JSON)
        //  return response()->json(['XEL' => 986576]);
        return response()->json($settings);
    }
}