<?php

/**
 * Снимок денег всех партнёров — для сверки до/после выката.
 *
 * Запуск (и на старом коде прода, и на новом):
 *   php artisan tinker tools/balance-snapshot.php
 *
 * Куда писать: переменная окружения PPM_SNAPSHOT_OUT, иначе константа
 * PPM_SNAPSHOT_DEFAULT_DIR ниже + имя balance-snapshot-<дата-время>.json.
 *
 * Скрипт только читает. Единственная запись, которая теоретически возможна, —
 * это `firstOrCreate` Joomla-пользователя внутри `getUserCoupons()`, поэтому
 * снимаем только партнёров с ОДОБРЕННОЙ заявкой: у них Joomla-пользователь
 * уже есть, создавать нечего. В свою БД скрипт не пишет ничего.
 *
 * Совместимость со старым кодом (коммит 8219482, прод до выката): используется
 * только `UserCouponController::data(Request)` (возвращает JsonResponse — так и
 * в старом, и в новом коде), `Auth::login()` и `setUserResolver()`. Сброс
 * статических кешей `JoomlaCoupon` — через `resetRequestCaches()`, если метод
 * есть (новый код), иначе рефлексией (в старом коде есть `$backendCache`).
 */

use App\Models\JoomlaCoupon;
use App\Models\PartnerApplication;
use App\Models\PayoutRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

defined('PPM_SNAPSHOT_DEFAULT_DIR') || define('PPM_SNAPSHOT_DEFAULT_DIR', 'app');   // относительно storage/
defined('PPM_SNAPSHOT_APPROVED_STATUS') || define('PPM_SNAPSHOT_APPROVED_STATUS', 2); // partner_applications.status_id = одобрено

$outPath = getenv('PPM_SNAPSHOT_OUT')
    ?: storage_path(PPM_SNAPSHOT_DEFAULT_DIR . '/balance-snapshot-' . date('Y-m-d_His') . '.json');

$say = function (string $line): void {
    fwrite(STDERR, $line . PHP_EOL);
};

/**
 * Гасит мемо-кеши JoomlaCoupon, живущие «на время запроса». Они не ключуются
 * по пользователю, поэтому в одном процессе данные протекают от партнёра к
 * партнёру (урок этапа W13).
 */
$resetJoomlaCaches = function () use ($say): void {
    if (method_exists(JoomlaCoupon::class, 'resetRequestCaches')) {
        JoomlaCoupon::resetRequestCaches();
        return;
    }

    // Старый код: публичного метода нет — сбрасываем статические свойства-кеши
    // по имени (всё, где есть «cache» или «failed»), не трогая настройки вроде
    // $ignore_groups.
    static $props = null;
    if ($props === null) {
        $props = [];
        foreach ((new ReflectionClass(JoomlaCoupon::class))->getProperties(ReflectionProperty::IS_STATIC) as $prop) {
            if (preg_match('/cache|failed/i', $prop->getName())) {
                $prop->setAccessible(true);
                $props[] = $prop;
            }
        }
        $say('[i] resetRequestCaches() нет — сбрасываю рефлексией: '
            . implode(', ', array_map(fn($p) => '$' . $p->getName(), $props)));
    }

    foreach ($props as $prop) {
        $current = $prop->getValue();
        $prop->setValue(null, is_array($current) ? [] : null);
    }
};

// Прогрев класса: в старом коде первая компиляция JoomlaCoupon.php поднимает
// ErrorException («continue» в switch), и первый же снимок падал бы зря.
// Этот вызов снимком не считается.
class_exists(JoomlaCoupon::class);
try {
    JoomlaCoupon::getUserPercentCouponsSummary(0);
} catch (Throwable $e) {
    $say('[i] прогрев класса: ' . get_class($e) . ' — ' . $e->getMessage());
}
$resetJoomlaCaches();

$userIds = PartnerApplication::where('status_id', PPM_SNAPSHOT_APPROVED_STATUS)
    ->pluck('user_id')->unique()->values();

$usersQuery = User::whereIn('id', $userIds)->orderBy('id');
// Сотрудники партнёрами не бывают (этап 1.3) — если метод отношения есть, отсеиваем.
if (method_exists(User::class, 'accessLevels')) {
    $usersQuery->whereDoesntHave('accessLevels');
}
$users = $usersQuery->get();

$say('[i] партнёров с одобренной заявкой: ' . $users->count());

$snapshot = [
    'taken_at' => date('c'),
    'code' => trim((string) @shell_exec('git -C ' . escapeshellarg(base_path()) . ' rev-parse --short HEAD 2>/dev/null')) ?: 'unknown',
    'partners' => [],
];

$number = 0;
foreach ($users as $user) {
    $number++;

    $resetJoomlaCaches();
    if (class_exists(\App\Helpers\BusinessDataCache::class)) {
        \App\Helpers\BusinessDataCache::forget($user->id);
    }

    $row = ['id' => $user->id, 'email' => $user->email];

    try {
        Auth::login($user);
        $request = Request::create('/api/user/business-data', 'GET');
        $request->setUserResolver(fn() => $user);
        app()->instance('request', $request);

        /** @var \Illuminate\Http\JsonResponse $response */
        $response = app(\App\Http\Controllers\UserCouponController::class)->data($request);
        $data = json_decode($response->getContent(), true);

        if (!is_array($data)) {
            throw new RuntimeException('пустой или нечитаемый ответ business-data');
        }

        $row['balance'] = $data['balance'] ?? null;
        $row['credits_total_accruals'] = $data['credits']['total_accruals'] ?? null;
        $row['credits_orders_count'] = $data['credits']['orders_count'] ?? null;
        $row['withdrawals_debit'] = $data['withdrawals']['debit'] ?? null;
        $row['payout_requests_debit'] = $data['payoutRequests']['debit'] ?? null;
        $row['total_bonus_codes_cost'] = $data['trueBonusCode']['totalBonusCodesCost'] ?? null;
        $row['backend_reversals_debit'] = $data['backendReversals']['debit'] ?? null;

        // Заявки на выплату по статусам: сумма и число (в том числе по статусам,
        // которых нет в settings.json — например, легаси 30).
        $byStatus = [];
        foreach (($data['payoutRequests']['payoutRequests'] ?? []) as $pr) {
            $status = (string) ($pr['status'] ?? 'null');
            $byStatus[$status]['count'] = ($byStatus[$status]['count'] ?? 0) + 1;
            $byStatus[$status]['sum'] = round(($byStatus[$status]['sum'] ?? 0) + (float) ($pr['withdrawal_amount'] ?? 0), 2);
        }
        ksort($byStatus, SORT_NATURAL);
        $row['payout_requests_by_status'] = $byStatus;
    } catch (Throwable $e) {
        $row['error'] = get_class($e) . ': ' . $e->getMessage();
    } finally {
        Auth::logout();
    }

    $snapshot['partners'][(string) $user->id] = $row;
    $say(sprintf('[%d/%d] #%d %s — %s', $number, $users->count(), $user->id, $user->email,
        $row['error'] ?? ('баланс ' . var_export($row['balance'], true))));
}

// Контрольные суммы по всем заявкам на выплату — видно сразу, если статусы
// поехали (например, легаси 30 исчез после миграции).
$snapshot['payout_requests_all'] = PayoutRequest::query()
    ->selectRaw('status, count(*) as cnt, sum(withdrawal_amount) as amount')
    ->groupBy('status')->orderBy('status')->get()
    ->mapWithKeys(fn($r) => [(string) $r->status => ['count' => (int) $r->cnt, 'sum' => (float) $r->amount]])
    ->all();

if (!is_dir(dirname($outPath))) {
    mkdir(dirname($outPath), 0775, true);
}
file_put_contents($outPath, json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

$say('[✓] снимок сохранён: ' . $outPath);
