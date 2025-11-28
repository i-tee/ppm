<?php

return [
    'requisite_fields' => [
        [
            'name' => 'full_name',
            'required' => true,
            'visible' => [1, 2, 3],
            'type' => 'text',
            'label' => 'requisites.full_name',
            'group' => 'basic',
            'order' => 10
        ],
        [
            'name' => 'passport_series',
            'required' => true,
            'visible' => [1, 2],
            'type' => 'text',
            'label' => 'requisites.passport_series',
            'group' => 'passport',
            'order' => 30
        ],
        [
            'name' => 'passport_number',
            'required' => true,
            'visible' => [1, 2],
            'type' => 'text',
            'label' => 'requisites.passport_number',
            'group' => 'passport',
            'order' => 40
        ],
        [
            'name' => 'passport_issued_date',
            'required' => true,
            'visible' => [1, 2],
            'type' => 'date',
            'label' => 'requisites.passport_issued_date',
            'group' => 'passport',
            'order' => 50
        ],
        [
            'name' => 'passport_issued_by',
            'required' => true,
            'visible' => [1, 2],
            'type' => 'text',
            'label' => 'requisites.passport_issued_by',
            'group' => 'passport',
            'order' => 60
        ],
        [
            'name' => 'passport_issued_by_code',
            'required' => true,
            'visible' => [1],
            'type' => 'text',
            'label' => 'requisites.passport_issued_by_code',
            'group' => 'passport',
            'order' => 65
        ],
        [
            'name' => 'birth_date',
            'required' => true,
            'visible' => [1],
            'type' => 'date',
            'label' => 'requisites.birth_date',
            'group' => 'passport',
            'order' => 20
        ],
        [
            'name' => 'passport_birth_place',
            'required' => true,
            'visible' => [1],
            'type' => 'text',
            'label' => 'requisites.passport_birth_place',
            'group' => 'passport',
            'order' => 67
        ],
        [
            'name' => 'passport_registration_address',
            'required' => true,
            'visible' => [1, 2, 3],
            'type' => 'text',
            'label' => 'requisites.passport_registration_address',
            'group' => 'passport',
            'order' => 70
        ],
        [
            'name' => 'birth_place',
            'required' => true,
            'visible' => [],
            'type' => 'text',
            'label' => 'requisites.birth_place',
            'group' => 'passport',
            'order' => 75
        ],
        [
            'name' => 'passport_snils',
            'required' => true,
            'visible' => [1],
            'type' => 'text',
            'label' => 'requisites.passport_snils',
            'group' => 'passport',
            'order' => 80
        ],
        [
            'name' => 'bank_card_number',
            'required' => false,
            'visible' => [1, 2],
            'type' => 'text',
            'label' => 'requisites.bank_card_number',
            'group' => 'bank',
            'order' => 90
        ],
        [
            'name' => 'bank_card_holder',
            'required' => false,
            'visible' => [],
            'type' => 'text',
            'label' => 'requisites.bank_card_holder',
            'group' => 'bank',
            'order' => 100
        ],
        [
            'name' => 'bank_phone_for_sbp',
            'required' => true,
            'visible' => [1, 2],
            'type' => 'text',
            'label' => 'requisites.bank_phone_for_sbp',
            'group' => 'bank',
            'order' => 110
        ],
        [
            'name' => 'tax_check_required',
            'required' => false,
            'visible' => [],
            'type' => 'checkbox',
            'label' => 'requisites.tax_check_required',
            'group' => 'basic',
            'default' => true,
            'order' => 120
        ],
        [
            'name' => 'org_inn',
            'required' => true,
            'visible' => [1, 2, 3, 4],
            'type' => 'text',
            'label' => 'requisites.org_inn',
            'group' => 'organization',
            'order' => 130
        ],
        [
            'name' => 'org_full_name',
            'required' => true,
            'visible' => [3, 4],
            'type' => 'text',
            'label' => 'requisites.org_full_name',
            'group' => 'organization',
            'order' => 140
        ],
        [
            'name' => 'org_short_name',
            'required' => true,
            'visible' => [4],
            'type' => 'text',
            'label' => 'requisites.org_short_name',
            'group' => 'organization',
            'order' => 150
        ],
        [
            'name' => 'org_legal_form',
            'required' => false,
            'visible' => [],
            'type' => 'text',
            'label' => 'requisites.org_legal_form',
            'group' => 'organization',
            'order' => 160
        ],
        [
            'name' => 'org_ogrnip',
            'required' => true,
            'visible' => [3],
            'type' => 'text',
            'label' => 'requisites.org_ogrnip',
            'group' => 'organization',
            'order' => 170
        ],
        [
            'name' => 'org_ogrn',
            'required' => true,
            'visible' => [4],
            'type' => 'text',
            'label' => 'requisites.org_ogrn',
            'group' => 'organization',
            'order' => 180
        ],
        [
            'name' => 'org_kpp',
            'required' => true,
            'visible' => [4],
            'type' => 'number',
            'label' => 'requisites.org_kpp',
            'group' => 'organization',
            'order' => 190
        ],
        [
            'name' => 'org_legal_address',
            'required' => true,
            'visible' => [4],
            'type' => 'text',
            'label' => 'requisites.org_legal_address',
            'group' => 'organization',
            'order' => 200
        ],
        [
            'name' => 'org_postal_address',
            'required' => true,
            'visible' => [4],
            'type' => 'text',
            'label' => 'requisites.org_postal_address',
            'group' => 'organization',
            'order' => 205
        ],
        [
            'name' => 'org_actual_address',
            'required' => true,
            'visible' => [4],
            'type' => 'text',
            'label' => 'requisites.org_actual_address',
            'group' => 'organization',
            'order' => 207
        ],
        [
            'name' => 'org_phone',
            'required' => true,
            'visible' => [3, 4],
            'type' => 'text',
            'label' => 'requisites.org_phone',
            'group' => 'organization',
            'order' => 209
        ],
        [
            'name' => 'org_email',
            'required' => false,
            'visible' => [3, 4],
            'type' => 'email',
            'label' => 'requisites.org_email',
            'group' => 'organization',
            'order' => 211
        ],
        [
            'name' => 'org_website',
            'required' => true,
            'visible' => [3, 4],
            'type' => 'text',
            'label' => 'requisites.org_website',
            'group' => 'organization',
            'order' => 213
        ],
        [
            'name' => 'org_director_name',
            'required' => true,
            'visible' => [3, 4],
            'type' => 'text',
            'label' => 'requisites.org_director_name',
            'group' => 'organization',
            'order' => 210
        ],
        [
            'name' => 'org_director_position',
            'required' => true,
            'visible' => [3, 4],
            'type' => 'text',
            'label' => 'requisites.org_director_position',
            'group' => 'organization',
            'order' => 220
        ],
        [
            'name' => 'org_director_basis',
            'required' => true,
            'visible' => [4],
            'type' => 'text',
            'label' => 'requisites.org_director_basis',
            'group' => 'organization',
            'order' => 230
        ],
        [
            'name' => 'org_tax_system',
            'required' => true,
            'visible' => [3, 4],
            'type' => 'select',
            'label' => 'requisites.org_tax_system',
            'group' => 'organization',
            'options' => ['ОСН', 'УСН', 'ЕНВД', 'Патент'],
            'order' => 240
        ],
        [
            'name' => 'bank_name',
            'required' => true,
            'visible' => [1, 2, 3, 4],
            'type' => 'text',
            'label' => 'requisites.bank_name',
            'group' => 'bank',
            'order' => 250
        ],
        [
            'name' => 'bank_bik',
            'required' => true,
            'visible' => [1, 2, 3, 4],
            'type' => 'number',
            'label' => 'requisites.bank_bik',
            'group' => 'bank',
            'order' => 260
        ],
        [
            'name' => 'bank_payment_account',
            'required' => false,
            'visible' => [],
            'type' => 'number',
            'label' => 'requisites.bank_payment_account',
            'group' => 'bank',
            'order' => 270
        ],
        [
            'name' => 'bank_correspondent_account',
            'required' => false,
            'visible' => [],
            'type' => 'number',
            'label' => 'requisites.bank_correspondent_account',
            'group' => 'bank',
            'order' => 280
        ],
        [
            'name' => 'bank_account_type',
            'required' => false,
            'visible' => [],
            'type' => 'select',
            'label' => 'requisites.bank_account_type',
            'group' => 'bank',
            'options' => ['settlement', 'card', 'personal'],
            'order' => 285
        ],
        [
            'name' => 'bank_account_number',
            'required' => true,
            'visible' => [1, 2, 3, 4],
            'type' => 'text',
            'label' => 'requisites.bank_account_number',
            'group' => 'bank',
            'order' => 290
        ],
        [
            'name' => 'additional_info',
            'required' => false,
            'visible' => [],
            'type' => 'textarea',
            'label' => 'requisites.additional_info',
            'group' => 'basic',
            'order' => 999
        ]
    ]
];
