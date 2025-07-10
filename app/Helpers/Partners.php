<?php

namespace App\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Arr;

class Partners
{
    /**
     * Получает настройки партнерской программы из config/settings.json
     *
     * @param string|null $key Ключ для получения конкретного значения или null для всего массива
     * @return mixed
     */
    public static function getSettings($key = null)
    {
        // Путь к файлу настроек
        $filePath = base_path('config/settings.json');

        // Проверяем, существует ли файл
        if (!File::exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        // Читаем содержимое файла
        $content = File::get($filePath);

        // Преобразуем JSON в массив
        $settings = json_decode($content, true);

        // Если указан ключ, возвращаем конкретное значение
        if ($key) {
            return Arr::get($settings, $key);
        }

        // Возвращаем весь массив настроек
        return $settings;
    }
}