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
    
}