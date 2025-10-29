<?php

namespace App\Helpers;

use Illuminate\Support\Collection;

class Requisites
{

    /**
     * Получить все поля реквизитов из конфига без обработок
     */
    public static function getFields()
    {
        return config('requisite.requisite_fields', []);
    }


    /**
     * Получить все поля реквизитов из конфига
     */
    public static function getAllFields(): Collection
    {
        return collect(config('requisite.requisite_fields', []));
    }

    /**
     * Получить поля для конкретного типа партнера
     * 
     * @param int $partnerTypeId ID типа партнера (1-физлицо, 2-самозанятый, 3-ИП, 4-компания)
     * @return Collection
     */
    public static function getFieldsForPartnerType(int $partnerTypeId): Collection
    {
        return self::getAllFields()
            ->filter(fn($field) => in_array($partnerTypeId, $field['visible']))
            ->values();
    }

    /**
     * Получить обязательные поля для типа партнера
     * 
     * @param int $partnerTypeId
     * @return Collection
     */
    public static function getRequiredFieldsForPartnerType(int $partnerTypeId): Collection
    {
        return self::getFieldsForPartnerType($partnerTypeId)
            ->filter(fn($field) => $field['required'] === true)
            ->values();
    }

    /**
     * Получить поля сгруппированные по группам для типа партнера
     * 
     * @param int $partnerTypeId
     * @return array
     */
    public static function getGroupedFieldsForPartnerType(int $partnerTypeId): array
    {
        return self::getFieldsForPartnerType($partnerTypeId)
            ->groupBy('group')
            ->toArray();
    }

    /**
     * Получить поля определенной группы для типа партнера
     * 
     * @param int $partnerTypeId
     * @param string $group (basic, passport, bank, organization)
     * @return Collection
     */
    public static function getFieldsByGroup(int $partnerTypeId, string $group): Collection
    {
        return self::getFieldsForPartnerType($partnerTypeId)
            ->filter(fn($field) => $field['group'] === $group)
            ->values();
    }

    /**
     * Получить настройки конкретного поля по имени
     * 
     * @param string $fieldName
     * @return array|null
     */
    public static function getFieldConfig(string $fieldName): ?array
    {
        return self::getAllFields()
            ->firstWhere('name', $fieldName);
    }

    /**
     * Проверить, является ли поле обязательным для типа партнера
     * 
     * @param string $fieldName
     * @param int $partnerTypeId
     * @return bool
     */
    public static function isFieldRequired(string $fieldName, int $partnerTypeId): bool
    {
        $field = self::getFieldConfig($fieldName);

        return $field &&
            in_array($partnerTypeId, $field['visible']) &&
            $field['required'] === true;
    }

    /**
     * Проверить, видимо ли поле для типа партнера
     * 
     * @param string $fieldName
     * @param int $partnerTypeId
     * @return bool
     */
    public static function isFieldVisible(string $fieldName, int $partnerTypeId): bool
    {
        $field = self::getFieldConfig($fieldName);

        return $field && in_array($partnerTypeId, $field['visible']);
    }

    /**
     * Получить список всех доступных групп полей
     * 
     * @return array
     */
    public static function getAvailableGroups(): array
    {
        return self::getAllFields()
            ->pluck('group')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Получить поля определенного типа (text, number, date, etc.)
     * 
     * @param string $fieldType
     * @param int|null $partnerTypeId - опционально, фильтр по типу партнера
     * @return Collection
     */
    public static function getFieldsByType(string $fieldType, ?int $partnerTypeId = null): Collection
    {
        $fields = self::getAllFields()
            ->filter(fn($field) => $field['type'] === $fieldType);

        if ($partnerTypeId) {
            $fields = $fields->filter(fn($field) => in_array($partnerTypeId, $field['visible']));
        }

        return $fields->values();
    }

    /**
     * Получить правила валидации для типа партнера
     * 
     * @param int $partnerTypeId
     * @return array
     */
    public static function getValidationRules(int $partnerTypeId): array
    {
        $rules = [];

        foreach (self::getFieldsForPartnerType($partnerTypeId) as $field) {
            $fieldRules = [];

            // Добавляем правило обязательности
            if ($field['required']) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            // Добавляем тип поля
            switch ($field['type']) {
                case 'number':
                    $fieldRules[] = 'numeric';
                    break;
                case 'date':
                    $fieldRules[] = 'date';
                    break;
                case 'email':
                    $fieldRules[] = 'email';
                    break;
                default:
                    $fieldRules[] = 'string';
            }

            // Добавляем кастомные правила валидации если есть
            if (isset($field['validation'])) {
                $fieldRules[] = $field['validation'];
            }

            $rules[$field['name']] = $fieldRules;
        }

        return $rules;
    }

    /**
     * Получить сообщения об ошибках валидации для типа партнера
     * 
     * @param int $partnerTypeId
     * @return array
     */
    public static function getValidationMessages(int $partnerTypeId): array
    {
        $messages = [];

        foreach (self::getFieldsForPartnerType($partnerTypeId) as $field) {
            $fieldName = $field['name'];
            $label = trans($field['label']);

            $messages["{$fieldName}.required"] = "Поле \"{$label}\" обязательно для заполнения.";
            $messages["{$fieldName}.numeric"] = "Поле \"{$label}\" должно быть числом.";
            $messages["{$fieldName}.date"] = "Поле \"{$fieldName}\" должно быть корректной датой.";
            $messages["{$fieldName}.email"] = "Поле \"{$fieldName}\" должно быть корректным email адресом.";
        }

        return $messages;
    }

    /**
     * Получить значения по умолчанию для типа партнера
     * 
     * @param int $partnerTypeId
     * @return array
     */
    public static function getDefaultValues(int $partnerTypeId): array
    {
        $defaults = [];

        foreach (self::getFieldsForPartnerType($partnerTypeId) as $field) {
            if (isset($field['default'])) {
                $defaults[$field['name']] = $field['default'];
            }
        }

        return $defaults;
    }

    /**
     * Проверить, заполнены ли все обязательные поля в данных
     * 
     * @param array $data
     * @param int $partnerTypeId
     * @return bool
     */
    public static function hasAllRequiredFields(array $data, int $partnerTypeId): bool
    {
        $requiredFields = self::getRequiredFieldsForPartnerType($partnerTypeId)
            ->pluck('name')
            ->toArray();

        foreach ($requiredFields as $field) {
            if (empty($data[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Получить список незаполненных обязательных полей
     * 
     * @param array $data
     * @param int $partnerTypeId
     * @return array
     */
    public static function getMissingRequiredFields(array $data, int $partnerTypeId): array
    {
        $missing = [];
        $requiredFields = self::getRequiredFieldsForPartnerType($partnerTypeId);

        foreach ($requiredFields as $field) {
            if (empty($data[$field['name']] ?? null)) {
                $missing[] = [
                    'name' => $field['name'],
                    'label' => trans($field['label'])
                ];
            }
        }

        return $missing;
    }

    /**
     * Фильтровать данные, оставляя только поля доступные для типа партнера
     * 
     * @param array $data
     * @param int $partnerTypeId
     * @return array
     */
    public static function filterDataForPartnerType(array $data, int $partnerTypeId): array
    {
        $allowedFields = self::getFieldsForPartnerType($partnerTypeId)
            ->pluck('name')
            ->toArray();

        return array_intersect_key($data, array_flip($allowedFields));
    }

    /**
     * Получить статистику по полям реквизитов
     * 
     * @return array
     */
    public static function getFieldsStatistics(): array
    {
        $allFields = self::getAllFields();

        return [
            'total_fields' => $allFields->count(),
            'fields_by_group' => $allFields->groupBy('group')->map->count()->toArray(),
            'fields_by_type' => $allFields->groupBy('type')->map->count()->toArray(),
            'required_fields_count' => $allFields->where('required', true)->count(),
            'fields_by_visibility' => [
                'individual' => $allFields->filter(fn($f) => in_array(1, $f['visible']))->count(),
                'self_employed' => $allFields->filter(fn($f) => in_array(2, $f['visible']))->count(),
                'entrepreneur' => $allFields->filter(fn($f) => in_array(3, $f['visible']))->count(),
                'company' => $allFields->filter(fn($f) => in_array(4, $f['visible']))->count(),
            ]
        ];
    }

    /**
     * Получить поля для формы создания реквизитов
     * 
     * @param int $partnerTypeId
     * @return array
     */
    public static function getFormFields(int $partnerTypeId): array
    {
        return self::getGroupedFieldsForPartnerType($partnerTypeId);
    }

    /**
     * Получить опции для селект-полей
     * 
     * @param string $fieldName
     * @return array
     */
    public static function getFieldOptions(string $fieldName): array
    {
        $field = self::getFieldConfig($fieldName);

        return $field['options'] ?? [];
    }

    /**
     * Проверить, существует ли поле
     * 
     * @param string $fieldName
     * @return bool
     */
    public static function fieldExists(string $fieldName): bool
    {
        return !is_null(self::getFieldConfig($fieldName));
    }
}

// Примеры использования: -----------------------------------------------------------------

// 📋 Фильтрация полей по типам партнеров, группам, типам данных
// ⚡ Валидация - автоматическая генерация правил валидации
// 🔍 Проверки - проверка заполненности, видимости полей
// 📊 Статистика - аналитика по полям реквизитов
// 🛡️ Безопасность - фильтрация данных перед сохранением
// 🎯 Группировка - организация полей по логическим группам

// use App\Helpers\Requisites;

// // 1. Получить все поля для физлица
// $individualFields = Requisites::getFieldsForPartnerType(1);

// // 2. Получить сгруппированные поля для ИП
// $groupedFields = Requisites::getGroupedFieldsForPartnerType(3);

// // 3. Получить правила валидации для самозанятого
// $validationRules = Requisites::getValidationRules(2);

// // 4. Проверить заполнение обязательных полей
// $isComplete = Requisites::hasAllRequiredFields($data, 1);

// // 5. Получить незаполненные поля
// $missingFields = Requisites::getMissingRequiredFields($data, 1);

// // 6. Фильтровать данные для типа партнера
// $filteredData = Requisites::filterDataForPartnerType($request->all(), 1);

// // 7. Получить статистику
// $stats = Requisites::getFieldsStatistics();
