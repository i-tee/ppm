<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Гейт финансовых роутов (реквизиты на проверку, выплаты): доступны админу
 * и бухгалтеру одинаково — уровень 1, 2 или 3 (User::canManageFinance).
 */
class EnsureUserCanManageFinance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->canManageFinance()) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        return $next($request);
    }
}
