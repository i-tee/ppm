<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Гейт админ-роутов (`/api/admin/users`, `/api/admin/impersonate/*`,
 * управление заявками партнёров): право «админ» = уровень доступа 1 или 2
 * (та же логика, что в User::isAdmin). Внутриконтроллерные проверки
 * остаются как дублирующая подстраховка.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        return $next($request);
    }
}
