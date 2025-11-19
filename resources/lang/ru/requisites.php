<?php

return [
    'validation_failed' => 'Проверьте правильность заполнения формы',
    'fix_errors'       => 'Исправьте следующие ошибки:',
    'invalid_partner_type' => 'Выбран недопустимый тип партнёра.',
    'partner_type'         => 'Тип партнёра',

    'required' => 'Поле «:attribute» обязательно для заполнения.',
    'email'    => 'Поле «:attribute» должно быть корректным email.',
    'date'     => 'Поле «:attribute» должно быть корректной датой.',
    'numeric'  => 'Поле «:attribute» должно содержать только цифры.',
    'regex'    => 'Поле «:attribute» имеет неверный формат.',

    // ←←← ВСЕ КЛЮЧИ ИЗ config/requisite.php — как в 'label' => 'что_то'
    'full_name'                  => 'ФИО',
    'birth_date'                 => 'Дата рождения',
    'passport_series'            => 'Серия паспорта',
    'passport_number'            => 'Номер паспорта',
    'passport_issued_date'       => 'Дата выдачи паспорта',
    'passport_issued_by'         => 'Кем выдан паспорт',
    'passport_issued_by_code'    => 'Код подразделения',
    'passport_birth_place'       => 'Место рождения',
    'passport_registration_address' => 'Адрес регистрации',
    'passport_snils'             => 'СНИЛС',
    'bank_card_number'           => 'Номер карты',
    'bank_phone_for_sbp'         => 'Телефон для СБП',
    'org_inn'                    => 'ИНН',
    'org_full_name'              => 'Полное наименование организации',
    'org_director_name'          => 'ФИО директора',
    'bank_name'                  => 'Наименование банка',
    'bank_bik'                   => 'БИК банка',
    'bank_account_number'        => 'Расчётный счёт',
    // Добавь остальные из твоего config/requisite.php — все 'поле'
];