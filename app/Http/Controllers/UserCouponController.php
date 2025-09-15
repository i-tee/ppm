<?php

// app/Http/Controllers/UserCouponController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JoomlaCoupon; // Твоя модель для работы с Joomla

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

    public function orders(Request $request)
    {

        $user = $request->user();
        $joomlaUser = JoomlaCoupon::joomlaUser();

        // 1. Получаем данные (то, что вы уже точно вызываете)
        $raw = JoomlaCoupon::getUserCoupons();   // может быть Collection, может быть массив

        // 2. Превращаем в коллекцию и берём только coupon_id
        $ids = collect($raw['coupons'] ?? $raw)  // если вернулся массив вида ['coupons'=>[...]]
            ->pluck('coupon_id')
            ->values();                       // сброс ключей

        $orders = []; // Здесь мы будем хранить заказы

        foreach ($ids as $id) {
            // Тут можно делать что-то с каждым id, если нужно
            // Например, логировать или проверять что-то
            $orders += JoomlaCoupon::getPpOrders($id);
        }

        $payments = JoomlaCoupon::getPpPayments($joomlaUser->id); // Получаем все платежи

        foreach ($orders as $order) {

            // coupon_id

        }

        // 3. Отдаём JSON
        return response()->json([
            'user' => $user,
            'joomlaUser' => $joomlaUser,
            'coupons' => $ids,
            'coupons_full' => $raw['coupons'],
            'payments' => $payments,
            'orders' => $orders
        ]);
    }
}
