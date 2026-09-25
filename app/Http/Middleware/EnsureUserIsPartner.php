<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Гейт партнёрских роутов: сотрудник (уровень 1, 2 или 3) не может быть
 * партнёром — партнёрские функции ему закрыты (решение владельца, 2026-09-25).
 * Impersonate это не ломает: под ним запросы идут с токеном партнёра.
 */
class EnsureUserIsPartner
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isStaff()) {
            return response()->json(['error' => 'Staff accounts cannot use partner features'], 403);
        }

        return $next($request);
    }
}
