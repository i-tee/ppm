<?php

namespace App\Http\Controllers;

use App\Helpers\BusinessDataCache;
use App\Helpers\Partners;
use App\Models\JoomlaCoupon;
use App\Models\PartnerApplication;
use App\Models\PayoutRequest;
use App\Models\Requisite;
use App\Models\TrueBonusCode;
use App\Models\User;
use App\Services\AvicennaBackendClient;
use App\Services\PartnerBalanceCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Админский экран «Партнёры» (этап В, PPM-W13): таблица активности и
 * карточка партнёра. Гейт – `admin` (бухгалтеру этот экран не нужен).
 *
 * Список НЕ зовёт `buildBusinessData()` на каждого партнёра (это десятки
 * запросов в Joomla и 2 HTTP в основной бэкенд на человека). Вместо этого –
 * несколько пакетных SELECT на всех сразу плюс `Http::pool` в бэкенд, а
 * баланс считает та же {@see PartnerBalanceCalculator}, что и кабинет
 * партнёра, – цифры в списке и в карточке обязаны совпадать.
 *
 * Если по партнёру что-то не догрузилось, его денежные цифры НЕ показываем
 * (`failed: true`) и такой список не кешируем: лучше честная «Ошибка
 * загрузки», чем неполные деньги. Подробности сбоя уходят только в лог.
 *
 * См. docs/operations.md §4в.
 */
class AdminPartnersController extends Controller
{
    /** Кеш готового списка – тот же `file`-стор, что у BusinessDataCache. */
    private const LIST_CACHE_KEY = 'ppm:partners-list';

    /** Время жизни списка, секунд. */
    private const LIST_CACHE_TTL = 300;

    /** Оплаченные заказы Joomla. */
    private const PAID_ORDER_STATUSES = [6, 7];

    /** Заявки на вывод, уменьшающие баланс (как в PayoutRequest::withdrawals). */
    private const PAYOUT_DEBIT_STATUSES = [
        PayoutRequest::STATUS_CREATED,
        PayoutRequest::STATUS_APPROVED,
        PayoutRequest::STATUS_PAID_WHAIT_TICKET,
        PayoutRequest::STATUS_TICKET_UPLOADED,
        PayoutRequest::STATUS_PAID,
    ];

    /** Заявка «в работе» – создана, но деньги ещё не ушли. */
    private const PAYOUT_PENDING_STATUSES = [
        PayoutRequest::STATUS_CREATED,
        PayoutRequest::STATUS_APPROVED,
        PayoutRequest::STATUS_PAID_WHAIT_TICKET,
        PayoutRequest::STATUS_TICKET_UPLOADED,
    ];

    /** Поля, по которым разрешена сортировка списка. */
    private const SORTABLE = [
        'name', 'email', 'created_at', 'balance', 'total_accruals',
        'orders_count', 'coupons_count', 'withdrawn_total', 'last_order_at',
        'last_payout_at', 'activity', 'application_status_id',
    ];

    // ══════════════════════════════════════════════════════════════════════
    // Список
    // ══════════════════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        $refresh = $request->boolean('refresh');

        $list = $refresh ? null : Cache::store('file')->get(self::LIST_CACHE_KEY);

        if ($list === null) {
            $list = $this->buildList();

            // Неполный список не кешируем – иначе «Обновить» пять минут
            // возвращало бы ту же дырявую выдачу.
            if (! $list['partial']) {
                Cache::store('file')->put(self::LIST_CACHE_KEY, $list, self::LIST_CACHE_TTL);
            }
        }

        $rows = $this->applyFilters($list['rows'], $request);
        $rows = $this->applySort($rows, $request);

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $page = max((int) $request->input('page', 1), 1);
        $total = count($rows);
        $lastPage = max((int) ceil($total / $perPage), 1);
        $page = min($page, $lastPage);

        return response()->json([
            'data' => array_values(array_slice($rows, ($page - 1) * $perPage, $perPage)),
            'current_page' => $page,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $total,
            // Хотя бы по одному партнёру данные неполные – фронт покажет
            // общее сообщение и кнопку «Обновить».
            'partial' => $list['partial'],
            'generated_at' => $list['generated_at'],
        ]);
    }

    /**
     * Собирает метрики по всем партнёрам пакетными запросами.
     *
     * @return array{rows:array,partial:bool,generated_at:string}
     */
    private function buildList(): array
    {
        $partners = User::whereDoesntHave('accessLevels')
            ->select('id', 'name', 'email', 'avatar', 'created_at', 'email_verified_at')
            ->orderBy('id')
            ->get();

        $ids = $partners->pluck('id')->all();

        if (empty($ids)) {
            return ['rows' => [], 'partial' => false, 'generated_at' => now()->toDateTimeString()];
        }

        $local = $this->loadLocalAggregates($ids);
        $joomla = $this->loadJoomlaAggregates($partners);
        $backend = app(AvicennaBackendClient::class)->getAccrualsRedemptionsBatch($ids);

        $activeDays = (int) (Partners::getSettings('partner_activity.active_days') ?: 60);
        $activeSince = now()->subDays($activeDays);

        $rows = [];
        $partial = false;

        foreach ($partners as $partner) {
            $application = $local['applications'][$partner->id] ?? null;

            $row = [
                'id' => $partner->id,
                'name' => $partner->name,
                'email' => $partner->email,
                'avatar' => $partner->avatar,
                'created_at' => optional($partner->created_at)->toDateTimeString(),
                'email_verified' => (bool) $partner->email_verified_at,
                'application_status_id' => $application?->status_id,
                'application_status_name' => $application?->status_name,
                'specialty' => $application?->specialty,
                'experience_years' => $application?->experience_years,
                'experience' => $application?->experience,
                'links' => $application?->links ?: [],
                'has_verified_requisites' => in_array($partner->id, $local['verifiedRequisites'], true),
            ];

            $backendForPartner = $backend[(string) $partner->id] ?? ['success' => false];

            // Сбой Joomla бьёт по всем, сбой бэкенда – только по своему партнёру.
            if ($joomla['failed'] || ! ($backendForPartner['success'] ?? false)) {
                $partial = true;
                $rows[] = $this->withoutMoney($row);
                continue;
            }

            $rows[] = $this->withMoney($row, $partner, $local, $joomla, $backendForPartner, $activeSince);
        }

        return [
            'rows' => $rows,
            'partial' => $partial,
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /** Строка партнёра без денежных цифр – их не догрузили. */
    private function withoutMoney(array $row): array
    {
        return $row + [
            'balance' => null,
            'total_accruals' => null,
            'orders_count' => null,
            'coupons_count' => null,
            'withdrawn_total' => null,
            'pending_payout_count' => null,
            'pending_payout_sum' => null,
            'last_payout_at' => null,
            'last_order_at' => null,
            'activity' => null,
            // Единственный признак для фронта: деньги этого партнёра не
            // показываем, вместо них — «Ошибка загрузки».
            'failed' => true,
        ];
    }

    /**
     * Считает деньги партнёра из уже загруженных пакетов – той же формулой,
     * что и кабинет партнёра, и с теми же поправками к заказам.
     */
    private function withMoney(
        array $row,
        User $partner,
        array $local,
        array $joomla,
        array $backendForPartner,
        Carbon $activeSince
    ): array {
        $joomlaUserId = $joomla['userIdByEmail'][mb_strtolower($partner->email)] ?? null;

        $classified = JoomlaCoupon::classifyBackendRows(
            $backendForPartner['accruals'] ?? [],
            $backendForPartner['redemptions'] ?? [],
        );

        $couponIds = $joomlaUserId !== null ? ($joomla['couponIdsByUser'][$joomlaUserId] ?? []) : [];

        // Заказы собираем ровно как getPpOrders(): поправка cashback по купону
        // плюс строки нового сайта по коду купона.
        $orders = [];
        $couponsCount = 0;

        foreach ($couponIds as $couponId) {
            $coupon = $joomla['couponsById'][$couponId] ?? null;
            if (! $coupon) {
                continue;
            }

            $couponsCount++;

            $couponOrders = JoomlaCoupon::applyCashbackFix(
                $coupon,
                collect($joomla['ordersByCoupon'][$couponId] ?? []),
                false, // список идёт по всем партнёрам разом – лог не забиваем
            );

            $couponOrders = JoomlaCoupon::appendBackendOrders(
                $couponOrders,
                (string) $coupon->coupon_code,
                (int) $couponId,
                $classified,
            );

            foreach ($couponOrders as $order) {
                $orders[] = $order;
            }
        }

        $reversalsDebit = 0.0;
        foreach ($classified['reversals'] as $reversal) {
            $reversalsDebit += abs((float) ($reversal['amount'] ?? 0));
        }
        $reversalsDebit = round($reversalsDebit, 2);

        $oldBalance = $joomlaUserId !== null ? (int) ($joomla['oldBalanceByUser'][$joomlaUserId] ?? 0) : 0;
        $legacy = $joomlaUserId !== null
            ? ($joomla['paymentsByUser'][$joomlaUserId] ?? ['debit' => 0.0, 'last_at' => null])
            : ['debit' => 0.0, 'last_at' => null];

        $payouts = $local['payouts'][$partner->id] ?? [
            'debit' => 0.0, 'paid' => 0.0, 'pending_count' => 0,
            'pending_sum' => 0.0, 'last_paid_at' => null,
        ];

        $totals = PartnerBalanceCalculator::compute(
            $orders,
            (float) $legacy['debit'],
            $oldBalance > 0 ? (float) $oldBalance : null,
            (float) ($local['bonusCodes'][$partner->id] ?? 0),
            (float) $payouts['debit'],
            $reversalsDebit,
        );

        $lastOrderAt = $this->maxOrderDate($orders);
        $lastPayoutAt = $this->maxDate([$legacy['last_at'], $payouts['last_paid_at']]);

        return $row + [
            'balance' => $totals['balance'],
            'total_accruals' => $totals['totalAccruals'],
            'orders_count' => $totals['ordersCount'],
            'coupons_count' => $couponsCount,
            'withdrawn_total' => round((float) $legacy['debit'] + (float) $payouts['paid'], 2),
            'pending_payout_count' => $payouts['pending_count'],
            'pending_payout_sum' => round((float) $payouts['pending_sum'], 2),
            'last_payout_at' => $lastPayoutAt,
            'last_order_at' => $lastOrderAt,
            'activity' => $this->activityBadge($totals['ordersCount'], $lastOrderAt, $activeSince),
            'failed' => false,
        ];
    }

    /**
     * Признак активности партнёра. Пороги – config/settings.json →
     * `partner_activity`, смысл – docs/operations.md §4в.
     */
    private function activityBadge(int $ordersCount, ?string $lastOrderAt, Carbon $activeSince): string
    {
        if ($ordersCount === 0 || $lastOrderAt === null) {
            return 'not_started';
        }

        return Carbon::parse($lastOrderAt)->greaterThanOrEqualTo($activeSince) ? 'active' : 'quiet';
    }

    /** Дата последнего заказа (оба источника), строкой или null. */
    private function maxOrderDate(array $orders): ?string
    {
        $max = null;

        foreach ($orders as $order) {
            $date = $order->order_date ?? null;
            if (! $date) {
                continue;
            }

            try {
                $parsed = Carbon::parse($date);
            } catch (\Throwable $e) {
                continue;
            }

            if ($max === null || $parsed->greaterThan($max)) {
                $max = $parsed;
            }
        }

        return $max?->toDateTimeString();
    }

    /** Максимум из набора дат (строки/null). */
    private function maxDate(array $dates): ?string
    {
        $max = null;

        foreach ($dates as $date) {
            if (! $date) {
                continue;
            }

            try {
                $parsed = Carbon::parse($date);
            } catch (\Throwable $e) {
                continue;
            }

            if ($max === null || $parsed->greaterThan($max)) {
                $max = $parsed;
            }
        }

        return $max?->toDateTimeString();
    }

    // ══════════════════════════════════════════════════════════════════════
    // Пакетные выборки
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Своя БД: анкеты, проверенные реквизиты, заявки на вывод, бонус-коды –
     * по одному запросу на всех партнёров.
     */
    private function loadLocalAggregates(array $ids): array
    {
        // Анкета – последняя по каждому партнёру.
        $applications = [];
        foreach (PartnerApplication::whereIn('user_id', $ids)->orderBy('id')->get() as $application) {
            $applications[$application->user_id] = $application;
        }

        $verifiedRequisites = Requisite::whereIn('user_id', $ids)
            ->where('is_verified', true)
            ->where('is_active', true)
            ->distinct()
            ->pluck('user_id')
            ->all();

        // Одна выборка вместо четырёх GROUP BY – из неё считаем и долг по
        // балансу, и «в работе», и «уже выплачено», и дату последней выплаты.
        $payouts = [];
        $payoutRows = PayoutRequest::whereIn('user_id', $ids)
            ->where('is_active', true)
            ->select('user_id', 'status', 'withdrawal_amount', 'created_at', 'updated_at')
            ->get();

        foreach ($payoutRows as $payout) {
            $bucket = &$payouts[$payout->user_id];
            $bucket ??= [
                'debit' => 0.0, 'paid' => 0.0, 'pending_count' => 0,
                'pending_sum' => 0.0, 'last_paid_at' => null,
            ];

            $amount = (float) $payout->withdrawal_amount;

            if (in_array($payout->status, self::PAYOUT_DEBIT_STATUSES, true)) {
                $bucket['debit'] += $amount;
            }

            if (in_array($payout->status, self::PAYOUT_PENDING_STATUSES, true)) {
                $bucket['pending_count']++;
                $bucket['pending_sum'] += $amount;
            }

            if ($payout->status === PayoutRequest::STATUS_PAID) {
                $bucket['paid'] += $amount;
                $paidAt = optional($payout->updated_at)->toDateTimeString();
                if ($paidAt && ($bucket['last_paid_at'] === null || $paidAt > $bucket['last_paid_at'])) {
                    $bucket['last_paid_at'] = $paidAt;
                }
            }

            unset($bucket);
        }

        $bonusCodes = TrueBonusCode::whereIn('user_id', $ids)
            ->selectRaw('user_id, SUM(bonus_code_cost) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id')
            ->all();

        return [
            'applications' => $applications,
            'verifiedRequisites' => $verifiedRequisites,
            'payouts' => $payouts,
            'bonusCodes' => $bonusCodes,
        ];
    }

    /**
     * Joomla: пять выборок на всех партнёров сразу (число запросов не растёт
     * с числом партнёров). Заказы – только белым списком полей, без ПДн
     * покупателя (docs/operations.md §5).
     */
    private function loadJoomlaAggregates($partners): array
    {
        $empty = [
            'failed' => false,
            'userIdByEmail' => [],
            'couponIdsByUser' => [],
            'oldBalanceByUser' => [],
            'couponsById' => [],
            'ordersByCoupon' => [],
            'paymentsByUser' => [],
        ];

        try {
            $emails = $partners->pluck('email')->filter()->values()->all();

            $joomlaUsers = DB::connection('mysql_joomla')
                ->table('users')
                ->whereIn('email', $emails)
                ->select('id', 'email')
                ->get();

            $userIdByEmail = [];
            foreach ($joomlaUsers as $joomlaUser) {
                $userIdByEmail[mb_strtolower($joomlaUser->email)] = (int) $joomlaUser->id;
            }

            $joomlaUserIds = array_values(array_unique($userIdByEmail));

            if (empty($joomlaUserIds)) {
                return $empty;
            }

            $records = DB::connection('mysql_joomla')
                ->table('avicenna_user_coupons')
                ->whereIn('user_id', $joomlaUserIds)
                ->select('user_id', 'coupons', 'old_balance')
                ->get();

            $couponIdsByUser = [];
            $oldBalanceByUser = [];
            $allCouponIds = [];

            foreach ($records as $record) {
                $userId = (int) $record->user_id;
                $oldBalanceByUser[$userId] = (int) ($record->old_balance ?? 0);

                // Разбор CSV – как в getUserCouponsUncached().
                $couponIds = array_filter(
                    array_map('intval', explode(',', (string) $record->coupons)),
                    fn ($id) => $id > 0
                );
                $couponIdsByUser[$userId] = array_values(array_unique($couponIds));
                $allCouponIds = array_merge($allCouponIds, $couponIdsByUser[$userId]);
            }

            $allCouponIds = array_values(array_unique($allCouponIds));

            $couponsById = [];
            $ordersByCoupon = [];

            if (! empty($allCouponIds)) {
                foreach (
                    DB::connection('mysql_joomla')
                        ->table('jshopping_coupons')
                        ->whereIn('coupon_id', $allCouponIds)
                        ->get() as $coupon
                ) {
                    $couponsById[(int) $coupon->coupon_id] = $coupon;
                }

                foreach (
                    DB::connection('mysql_joomla')
                        ->table('jshopping_orders')
                        // Белый список полей – без ПДн покупателя (152-ФЗ).
                        ->select(JoomlaCoupon::orderSafeFields())
                        ->whereIn('coupon_id', $allCouponIds)
                        ->whereIn('order_status', self::PAID_ORDER_STATUSES)
                        ->get() as $order
                ) {
                    $ordersByCoupon[(int) $order->coupon_id][] = $order;
                }
            }

            // Выплаты старой партнёрки – тоже пакетом, иначе баланс в списке
            // разошёлся бы с кабинетом партнёра.
            $paymentsByUser = [];
            foreach (
                DB::connection('mysql_joomla')
                    ->table('avicenna_pp_payments')
                    ->whereIn('user_id', $joomlaUserIds)
                    ->select('user_id', 'summ', 'date_exec')
                    ->get() as $payment
            ) {
                $userId = (int) $payment->user_id;
                $paymentsByUser[$userId] ??= ['debit' => 0.0, 'last_at' => null];
                $paymentsByUser[$userId]['debit'] += (float) $payment->summ;

                $executedAt = $payment->date_exec ? (string) $payment->date_exec : null;
                if ($executedAt && ($paymentsByUser[$userId]['last_at'] === null
                    || $executedAt > $paymentsByUser[$userId]['last_at'])) {
                    $paymentsByUser[$userId]['last_at'] = $executedAt;
                }
            }

            return [
                'failed' => false,
                'userIdByEmail' => $userIdByEmail,
                'couponIdsByUser' => $couponIdsByUser,
                'oldBalanceByUser' => $oldBalanceByUser,
                'couponsById' => $couponsById,
                'ordersByCoupon' => $ordersByCoupon,
                'paymentsByUser' => $paymentsByUser,
            ];
        } catch (\Throwable $e) {
            // Детали – только в лог: наружу уходит обезличенная «Ошибка загрузки».
            Log::error('admin.partners_joomla_failed', ['error' => $e->getMessage()]);

            return array_merge($empty, ['failed' => true]);
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // Поиск, фильтры, сортировка
    // ══════════════════════════════════════════════════════════════════════

    private function applyFilters(array $rows, Request $request): array
    {
        $query = trim((string) $request->input('q', ''));
        if ($query !== '') {
            $needle = mb_strtolower($query);
            $rows = array_filter($rows, fn ($row) => str_contains(mb_strtolower((string) $row['name']), $needle)
                || str_contains(mb_strtolower((string) $row['email']), $needle));
        }

        if ($request->filled('status_id')) {
            $statusId = (int) $request->input('status_id');
            $rows = array_filter($rows, fn ($row) => $row['application_status_id'] === $statusId);
        }

        if ($request->filled('activity')) {
            $activity = (string) $request->input('activity');
            $rows = array_filter($rows, fn ($row) => $row['activity'] === $activity);
        }

        return array_values($rows);
    }

    private function applySort(array $rows, Request $request): array
    {
        $sort = (string) $request->input('sort', 'created_at');
        if (! in_array($sort, self::SORTABLE, true)) {
            $sort = 'created_at';
        }

        $desc = strtolower((string) $request->input('dir', 'desc')) !== 'asc';

        usort($rows, function ($a, $b) use ($sort, $desc) {
            $left = $a[$sort] ?? null;
            $right = $b[$sort] ?? null;

            // Строки со сбоем загрузки (null в деньгах) – всегда в конце,
            // в каком бы направлении ни сортировали.
            if ($left === null && $right === null) {
                return 0;
            }
            if ($left === null) {
                return 1;
            }
            if ($right === null) {
                return -1;
            }

            $result = is_numeric($left) && is_numeric($right)
                ? $left <=> $right
                : strcasecmp((string) $left, (string) $right);

            return $desc ? -$result : $result;
        });

        return $rows;
    }

    // ══════════════════════════════════════════════════════════════════════
    // Карточка партнёра
    // ══════════════════════════════════════════════════════════════════════

    public function show(Request $request, int $id)
    {
        $partner = User::find($id);

        if (! $partner) {
            return response()->json(['message' => 'errors.user_not_found'], 404);
        }

        $applications = PartnerApplication::where('user_id', $partner->id)
            ->orderByDesc('id')
            ->get();

        $requisites = Requisite::where('user_id', $partner->id)
            ->orderByDesc('id')
            ->get();

        $payoutRequests = PayoutRequest::where('user_id', $partner->id)
            ->with('requisite')
            ->orderByDesc('created_at')
            ->get();

        [$businessData, $hasJoomlaUser, $failed] = $this->loadPartnerBusinessData(
            $partner,
            $request->boolean('refresh'),
        );

        return response()->json([
            'partner' => [
                'id' => $partner->id,
                'name' => $partner->name,
                'email' => $partner->email,
                'avatar' => $partner->avatar,
                'created_at' => optional($partner->created_at)->toDateTimeString(),
                'email_verified' => (bool) $partner->email_verified_at,
            ],
            'applications' => $applications,
            'requisites' => $requisites,
            'payoutRequests' => $payoutRequests,
            'businessData' => $businessData,
            // Партнёр ещё не заходил в магазин – денежных блоков нет, но это
            // не ошибка: показываем «ещё не начал».
            'hasJoomlaUser' => $hasJoomlaUser,
            'failed' => $failed,
        ]);
    }

    /**
     * Данные кабинета партнёра под админом. `JoomlaCoupon` берёт пользователя
     * из `Auth`, поэтому подменяем его на партнёра – строго в `try/finally`,
     * с гарантированным возвратом админа и сбросом мемо-кешей модели по обе
     * стороны подмены (часть кешей глобальная, не по id пользователя).
     *
     * @return array{0:array|null,1:bool,2:bool} [businessData, hasJoomlaUser, failed]
     */
    private function loadPartnerBusinessData(User $partner, bool $refresh): array
    {
        $admin = Auth::user();
        $businessData = null;
        $hasJoomlaUser = false;
        $failed = false;

        try {
            Auth::setUser($partner);
            JoomlaCoupon::resetRequestCaches();

            $cacheKey = BusinessDataCache::businessDataKey($partner->id);
            $businessData = $refresh ? null : Cache::store('file')->get($cacheKey);

            if ($businessData !== null) {
                // Данные в кеше есть – значит и Joomla-пользователь есть,
                // лишний запрос в Joomla ради этого не делаем.
                $hasJoomlaUser = true;
            } elseif (JoomlaCoupon::joomlaUser()) {
                // Сначала чистый SELECT: getUserCoupons() умеет СОЗДАТЬ запись в
                // Joomla, а админский просмотр не должен ничего создавать.
                $hasJoomlaUser = true;

                $businessData = app(UserCouponController::class)->buildBusinessData($partner);

                if (JoomlaCoupon::backendLoadFailed()) {
                    // Часть данных не пришла – ни показывать, ни кешировать.
                    $failed = true;
                    $businessData = null;
                } else {
                    Cache::store('file')->put($cacheKey, $businessData, BusinessDataCache::TTL);
                }
            }
        } catch (\Throwable $e) {
            Log::error('admin.partner_card_failed', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
            ]);

            $failed = true;
            $businessData = null;
        } finally {
            Auth::setUser($admin);
            JoomlaCoupon::resetRequestCaches();
        }

        return [$businessData, $hasJoomlaUser, $failed];
    }
}
