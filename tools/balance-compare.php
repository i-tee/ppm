<?php

/**
 * Сравнение двух снимков `tools/balance-snapshot.php`.
 *
 * Запуск:
 *   PPM_SNAPSHOT_A=<до.json> PPM_SNAPSHOT_B=<после.json> \
 *     php artisan tinker tools/balance-compare.php
 *
 * Расхождения печатаются таблицей «партнёр | поле | было | стало | разница».
 * Ожидаемые расхождения от миграции легаси-статуса 30 → 16 (заявки декабря
 * 2025, которые баланс не учитывал) выносятся отдельным блоком и в итог
 * «расхождений нет» не засчитываются как проблема.
 *
 * Скрипт читает только файлы, в БД не ходит.
 */

defined('PPM_LEGACY_STATUS') || define('PPM_LEGACY_STATUS', '30');
defined('PPM_LEGACY_STATUS_NEW') || define('PPM_LEGACY_STATUS_NEW', '16');

$pathA = getenv('PPM_SNAPSHOT_A');
$pathB = getenv('PPM_SNAPSHOT_B');

if (!$pathA || !$pathB) {
    // Без переменных берём два самых свежих снимка в storage/app.
    $found = glob(storage_path('app/balance-snapshot-*.json')) ?: [];
    sort($found);
    $pathA = $pathA ?: ($found[count($found) - 2] ?? null);
    $pathB = $pathB ?: ($found[count($found) - 1] ?? null);
}

$out = function (string $line = ''): void {
    fwrite(STDOUT, $line . PHP_EOL);
};

if (!$pathA || !$pathB || !is_file($pathA) || !is_file($pathB)) {
    $out('Нужны два файла снимков: PPM_SNAPSHOT_A=<до> PPM_SNAPSHOT_B=<после>.');
    return;
}

$a = json_decode((string) file_get_contents($pathA), true);
$b = json_decode((string) file_get_contents($pathB), true);

if (!is_array($a) || !is_array($b)) {
    $out('Не удалось прочитать снимки как JSON.');
    return;
}

$out('До:    ' . $pathA . '  (' . ($a['taken_at'] ?? '?') . ', код ' . ($a['code'] ?? '?') . ')');
$out('После: ' . $pathB . '  (' . ($b['taken_at'] ?? '?') . ', код ' . ($b['code'] ?? '?') . ')');
$out();

$fields = [
    'balance' => 'баланс',
    'credits_total_accruals' => 'начислено всего',
    'credits_orders_count' => 'оплаченных заказов',
    'withdrawals_debit' => 'списания (старая партнёрка)',
    'payout_requests_debit' => 'заявки на выплату, итог',
    'total_bonus_codes_cost' => 'бонус-коды, итог',
    'backend_reversals_debit' => 'сторно нового сайта',
];

$partnersA = $a['partners'] ?? [];
$partnersB = $b['partners'] ?? [];

$expected = [];   // ожидаемые: у партнёра в снимке «до» были заявки статуса 30
$unexpected = []; // всё остальное
$notes = [];      // появились/пропали партнёры, ошибки сборки

foreach (array_keys($partnersA + $partnersB) as $id) {
    $rowA = $partnersA[$id] ?? null;
    $rowB = $partnersB[$id] ?? null;

    if (!$rowA || !$rowB) {
        $notes[] = sprintf('#%s %s — есть только в снимке «%s»', $id,
            ($rowA['email'] ?? $rowB['email'] ?? '?'), $rowA ? 'до' : 'после');
        continue;
    }
    if (isset($rowA['error']) || isset($rowB['error'])) {
        $notes[] = sprintf('#%s %s — ошибка сборки: %s', $id, $rowA['email'] ?? '?',
            $rowA['error'] ?? $rowB['error']);
        continue;
    }

    // Признак «этого партнёра затронула миграция статуса 30».
    $hadLegacy = isset($rowA['payout_requests_by_status'][PPM_LEGACY_STATUS]);
    $legacySum = (float) ($rowA['payout_requests_by_status'][PPM_LEGACY_STATUS]['sum'] ?? 0);

    foreach ($fields as $key => $title) {
        $was = $rowA[$key] ?? null;
        $now = $rowB[$key] ?? null;
        if ((string) $was === (string) $now) {
            continue;
        }

        $diff = (is_numeric($was) && is_numeric($now)) ? round($now - $was, 2) : null;
        $item = [
            'id' => $id,
            'email' => $rowA['email'] ?? '?',
            'field' => $title,
            'was' => $was,
            'now' => $now,
            'diff' => $diff,
            'legacy_sum' => $legacySum,
        ];

        // Ожидаемо: у партнёра были заявки статуса 30, и баланс уменьшился ровно
        // на их сумму, а заявки переехали в статус 16.
        $isExpected = $hadLegacy && (
            ($key === 'balance' && $diff !== null && abs($diff + $legacySum) < 0.01)
            || ($key === 'payout_requests_debit' && $diff !== null && abs($diff - $legacySum) < 0.01)
        );

        $isExpected ? $expected[] = $item : $unexpected[] = $item;
    }

    // Отдельно — движение самих заявок по статусам.
    $statusesA = $rowA['payout_requests_by_status'] ?? [];
    $statusesB = $rowB['payout_requests_by_status'] ?? [];
    foreach (array_keys($statusesA + $statusesB) as $status) {
        $wasSum = (float) ($statusesA[$status]['sum'] ?? 0);
        $nowSum = (float) ($statusesB[$status]['sum'] ?? 0);
        if (abs($wasSum - $nowSum) < 0.01) {
            continue;
        }
        $item = [
            'id' => $id,
            'email' => $rowA['email'] ?? '?',
            'field' => 'заявки в статусе ' . $status . ', сумма',
            'was' => $wasSum,
            'now' => $nowSum,
            'diff' => round($nowSum - $wasSum, 2),
            'legacy_sum' => $legacySum,
        ];

        $isExpected = $hadLegacy && (
            ((string) $status === PPM_LEGACY_STATUS && abs($nowSum) < 0.01)
            || ((string) $status === PPM_LEGACY_STATUS_NEW && abs(($nowSum - $wasSum) - $legacySum) < 0.01)
        );

        $isExpected ? $expected[] = $item : $unexpected[] = $item;
    }
}

$table = function (array $rows) use ($out): void {
    $head = ['партнёр', 'поле', 'было', 'стало', 'разница'];
    $body = array_map(fn($r) => [
        '#' . $r['id'] . ' ' . $r['email'],
        $r['field'],
        (string) var_export($r['was'], true),
        (string) var_export($r['now'], true),
        $r['diff'] === null ? '—' : ($r['diff'] > 0 ? '+' : '') . $r['diff'],
    ], $rows);

    $width = [];
    foreach (array_merge([$head], $body) as $line) {
        foreach ($line as $i => $cell) {
            $width[$i] = max($width[$i] ?? 0, mb_strlen($cell));
        }
    }
    $render = function (array $line) use ($width, $out): void {
        $cells = [];
        foreach ($line as $i => $cell) {
            $cells[] = $cell . str_repeat(' ', $width[$i] - mb_strlen($cell));
        }
        $out('  ' . implode('  ', $cells));
    };
    $render($head);
    $render(array_map(fn($w) => str_repeat('-', $w), $width));
    foreach ($body as $line) {
        $render($line);
    }
};

if ($expected) {
    $sum = 0.0;
    foreach ($expected as $item) {
        if ($item['field'] === 'баланс' && $item['diff'] !== null) {
            $sum += $item['diff'];
        }
    }
    $out('Ожидаемо: статус 30 → 16 (миграция легаси-заявок)');
    $table($expected);
    $out(sprintf('  Итого баланс уменьшился на %s у %d партнёров.', number_format(abs($sum), 2, '.', ' '),
        count(array_unique(array_column(array_filter($expected, fn($i) => $i['field'] === 'баланс'), 'id')))));
    $out();
}

if ($unexpected) {
    $out('Расхождения:');
    $table($unexpected);
    $out();
}

foreach ($notes as $note) {
    $out('Внимание: ' . $note);
}
if ($notes) {
    $out();
}

$out($unexpected
    ? sprintf('ИТОГ: %d расхождений (кроме ожидаемых).', count($unexpected))
    : 'ИТОГ: расхождений нет' . ($expected ? ' (кроме ожидаемых выше).' : '.'));
