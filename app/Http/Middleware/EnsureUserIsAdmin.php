<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Гейт админ-роутов (`/api/admin/*`).
 *
 * До Фазы D эти роуты были защищены только `auth:sanctum` (= «залогинен»),
 * а проверка роли делалась вразнобой внутри контроллеров — часть методов
 * (напр. adminIndex «список выплат всех») её не имела вовсе, т.е. любой
 * авторизованный партнёр мог их дёрнуть. Этот middleware — централизованный
 * гейт: право «админ» = уровень доступа 1 или 2 (та же логика, что в
 * User::getEffectiveAccessLevelsAttribute / UserAccessLevel::isAdmin и в
 * самих контроллерах). Внутриконтроллерные проверки остаются как
 * дублирующая подстраховка.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $levels = $request->user()?->effective_access_levels ?? [];

        if (! (in_array(1, $levels) || in_array(2, $levels))) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        return $next($request);
    }
}
