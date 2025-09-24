<?php

// app/Http/Controllers/UserCouponController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\JoomlaCoupon; // модель для работы с Joomla
use App\Models\TrueBonusCode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class UserCouponController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            // Получаем промокоды пользователя из Joomla
            $couponsData = JoomlaCoupon::getUserCoupons();

            if (!$couponsData['success']) {
                return response()->json([
                    'message' => $couponsData['message']
                ], 404);
            }

            return response()->json([
                'coupons' => $couponsData['coupons'],
                'count' => $couponsData['coupons_count']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ошибка при получении промокодов',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function data(Request $request)
    {

        $user = $request->user();
        $joomlaUser = JoomlaCoupon::joomlaUser();
        $expenseSummary = 0;

        $balance = 0;
        $oldPromocodBalance = JoomlaCoupon::oldPromocodBalance($joomlaUser->id);

        // 1. Получаем данные (то, что вы уже точно вызываете)
        $raw = JoomlaCoupon::getUserCoupons();   // может быть Collection, может быть массив

        // 2. Превращаем в коллекцию и берём только coupon_id
        $ids = collect($raw['coupons'] ?? $raw)  // если вернулся массив вида ['coupons'=>[...]]
            ->pluck('coupon_id')
            ->values();                       // сброс ключей

        $orders = collect([]);
        foreach ($ids as $id) {
            $orders = $orders->merge(JoomlaCoupon::getPpOrders($id));
        }
        $orders = $orders->values()->toArray(); // Сбрасываем ключи и преобразуем в массив

        // $payments = JoomlaCoupon::getPpPayments($joomlaUser->id); // Получаем все платежи

        $coupon_types = [];
        foreach ($raw['coupons'] as $coupon) {
            $coupon_types[$coupon->coupon_id] = $coupon->coupon_type;
        }

        foreach ($orders as $order) {
            $order->coupon_type = $coupon_types[$order->coupon_id]; //$order->coupon_id
            // Очень плохая реализации через запросы в БД из перебора - исправить
        }

        $credits = JoomlaCoupon::credits();
        $withdrawals = JoomlaCoupon::withdrawals();

        $expenseSummary += $withdrawals['debit'];

        $balance = ceil($credits['total_accruals'] - $withdrawals['debit']);
        if ($oldPromocodBalance['be']) {
            $balance += $oldPromocodBalance['summ'];
        }

        $trueBonusCode = [];
        $trueBonusCode['trueBonusCodes'] = TrueBonusCode::where('user_id', $user->id)->orderBy('created_at')->get();
        $trueBonusCode['totalBonusCodesCost'] = $trueBonusCode['trueBonusCodes']->sum('bonus_code_cost');

        if (isset($trueBonusCode['totalBonusCodesCost']) and $trueBonusCode['totalBonusCodesCost'] > 0) {
            $balance -= $trueBonusCode['totalBonusCodesCost'];
            $expenseSummary += $trueBonusCode['totalBonusCodesCost'];
        }

        // 3. Отдаём JSON
        return response()->json([
            'user' => $user,
            'balance' => $balance,
            'expenseSummary' => $expenseSummary,
            'joomlaUser' => $joomlaUser,
            'oldPromocodBalance' => $oldPromocodBalance,
            'credits' => $credits,
            'withdrawals' => $withdrawals,
            'coupon_types' => $coupon_types,
            'coupons' => $ids,
            'coupons_full' => $raw['coupons'],
            'trueBonusCode' => $trueBonusCode,
            // 'payments' => $payments,
            // 'orders' => $orders
        ]);

        //$withdrawals = JoomlaCoupon::withdrawals();
    }

    public function create(Request $request)
    {
        // Получаем все данные из запроса
        $data = $request->all();

        // Валидация входных данных
        $validator = Validator::make($data, [
            'name' => 'required|string|min:6|max:36|regex:/^[A-Za-zА-Яа-яЁё0-9_-]+$/',
            'value' => 'required|integer|min:0',
            'type' => 'required|in:0,1',
            'joomlaUser' => 'required|integer' // joomlaUser — это ID пользователя в Joomla
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => __('errors.validation_failed'),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Попытка создания купона
            $result = JoomlaCoupon::createCoupon(
                $data['name'],
                $data['value'],
                $data['joomlaUser'],
                $data['type']
            );

            if ($result['success']) {
                return response()->json([
                    'message' => __('coupons.create_success')
                ], 200);
            } else {
                return response()->json([
                    'message' => __('errors.' . $result['error'])
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('errors.unexpected_error')
            ], 500);
        }
    }

    public static function withdrawals()
    {

        $withdrawals = JoomlaCoupon::withdrawals();

        return response()->json([
            'debit' => $withdrawals['debit'],
            'withdrawals' => $withdrawals['withdrawals']
        ], 200);
    }

    public static function credits()
    {
        $credits = JoomlaCoupon::credits();

        return response()->json([
            'total_accruals' => $credits['total_accruals'], // Округляем до 2 знаков
            'orders_count' => $credits['orders_count'], // Счётчик заказов
        ], 200);
    }

    public static function oldBalance()
    {
        $joomlaUser = JoomlaCoupon::joomlaUser();
        $oldBalance = JoomlaCoupon::oldPromocodBalance($joomlaUser->id);

        return response()->json([
            'oldBalance' => $oldBalance
        ], 200);
    }
}
