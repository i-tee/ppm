<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DevController extends Controller
{
    public function index(Request $request)
    {
        try {

            $t = DB::connection('mysql_joomla')->table('avicenna_quiz_steps')->get();

            return response()->json($t);

            // // Убедись, что имя соединения совпадает с config/database.php
            // $joomlaUsers = DB::connection('joomla_mysql')
            //     ->table('jm_avicenna_quiz_steps') // имя таблицы без префикса, если он указан в конфиге
            //     ->get();

            // return response()->json([
            //     'success' => true,
            //     'data' => $joomlaUsers,
            //     'count' => $joomlaUsers->count(),
            // ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка подключения к БД Joomla',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function dev(Request $request)
    {
        // Проверяем, существует ли соединение в конфиге
        $connections = config('database.connections');
        if (!array_key_exists('mysql_joomla', $connections)) {
            return response()->json([
                'error' => 'Соединение mysql_joomla НЕ найдено в конфиге!',
                'available_connections' => array_keys($connections)
            ], 500);
        }

        // Проверяем параметры подключения
        $joomlaConfig = config('database.connections.mysql_joomla');
        return response()->json([
            'connection_config' => $joomlaConfig,
            'env_check' => [
                'DB_JOOMLA_HOST' => env('DB_JOOMLA_HOST'),
                'DB_JOOMLA_PASSWORD' => env('DB_JOOMLA_PASSWORD') ? 'SET (length: ' . strlen(env('DB_JOOMLA_PASSWORD')) . ')' : 'NOT SET'
            ]
        ]);
    }
}
