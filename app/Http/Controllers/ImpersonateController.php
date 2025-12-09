<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ImpersonateController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Используем ту же логику, что и во фронтенде
        $effectiveLevels = $user->effective_access_levels ?? [];
        $isAdmin = in_array(1, $effectiveLevels) || in_array(2, $effectiveLevels);
        
        if (!$isAdmin) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $users = User::select('id', 'name', 'email', 'avatar', 'email_verified_at')
                     ->orderBy('created_at', 'desc')
                     ->paginate(20);

        return response()->json($users);
    }

    public function impersonate(Request $request, User $user)
    {
        $admin = Auth::user();
        
        // Проверка прав админа (та же логика)
        $adminEffectiveLevels = $admin->effective_access_levels ?? [];
        $isAdmin = in_array(1, $adminEffectiveLevels) || in_array(2, $adminEffectiveLevels);
        
        if (!$isAdmin) {
            return response()->json(['error' => 'Access denied'], 403);
        }
        
        if ($admin->id === $user->id) {
            return response()->json(['error' => 'Cannot impersonate yourself'], 400);
        }

        // Логирование в Cache для аудита
        $cacheKey = 'impersonation_' . $user->id . '_by_' . $admin->id;
        Cache::put($cacheKey, [
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'started_at' => now()->toDateTimeString(),
        ], now()->addHours(24));

        // Создаём токен для целевого юзера
        $token = $user->createToken(
            'impersonation-token-' . $admin->id,
            ['*'],
            now()->addHours(24)
        )->plainTextToken;

        // Загружаем отношения, если нужны
        $user->load('accessLevels');

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
                'effective_access_levels' => $user->effective_access_levels,
            ]
        ]);
    }

    public function stop(Request $request)
    {
        $user = Auth::user(); // Это будет impersonated user
        
        // Ищем cache запись об impersonation для этого пользователя
        $cacheKeys = Cache::get('impersonation_keys', []);
        $cacheKey = null;
        
        foreach ($cacheKeys as $key) {
            if (str_contains($key, '_' . $user->id . '_by_')) {
                $cacheKey = $key;
                break;
            }
        }
        
        if ($cacheKey) {
            Cache::forget($cacheKey);
            // Обновляем список ключей
            $cacheKeys = array_filter($cacheKeys, fn($k) => $k !== $cacheKey);
            Cache::put('impersonation_keys', $cacheKeys, now()->addHours(24));
        }

        // Удаляем текущий токен
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Impersonation stopped']);
    }
}