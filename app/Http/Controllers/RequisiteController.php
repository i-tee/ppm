<?php

namespace App\Http\Controllers;

use App\Models\Requisite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequisiteController extends Controller
{
    /**
     * Получить список реквизитов текущего пользователя.
     */
    public function index()
    {
        $requisites = Requisite::where('user_id', Auth::id())->get();
        return response()->json($requisites);
    }

    /**
     * Получить все реквизиты (для админов — unverified; для юзеров — свои).
     * Пагинация и сортировка на фронте.
     */
    public function all(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->hasAccessLevel(1) || $user->hasAccessLevel(2); // Проверка через UserAccessLevel

        $query = Requisite::query();

        if ($isAdmin) {
            // Для админов: все неверифицированные (без active(), без with('user'))
            $query->where('is_verified', false);
        } else {
            // Для юзеров: свои активные верифицированные
            $query->where('user_id', $user->id)->active()->verified();
        }

        // Дефолтная сортировка (фронт может переопределить клиентски)
        $query->orderBy('created_at', 'desc');
        $requisites = $query->get();

        return response()->json($requisites);
    }

    /**
     * Верифицировать реквизиты (только для админов).
     */
    public function verify($id)
    {
        $user = Auth::user();
        $isAdmin = $user->hasAccessLevel(1) || $user->hasAccessLevel(2); // Проверка через UserAccessLevel

        if (!$isAdmin) {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }

        $requisite = Requisite::findOrFail($id);
        $requisite->is_verified = true;
        $requisite->save();

        return response()->json([
            'message' => 'Реквизиты верифицированы',
            'requisite' => $requisite
        ]);
    }

    /**
     * Создать новые реквизиты для пользователя.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'partner_type_id' => 'required|integer',
            'inn' => 'nullable|string|max:20' // если хотите сохранить только эту валидацию
        ]);

        $data['user_id'] = Auth::id();

        // Добавляем все остальные поля
        $allData = array_merge($data, $request->except(['partner_type_id', 'inn']));

        $requisite = new Requisite($allData);
        $requisite->save();

        return response()->json($requisite, 201);
    }

    /**
     * Удалить реквизиты (мягкое удаление через SoftDeletes).
     */
    public function destroy($id)
    {
        $requisite = Requisite::where('user_id', Auth::id())->findOrFail($id);

        // Устанавливаем is_active = false и сохраняем
        $requisite->is_active = false;
        $requisite->save();

        $requisite->delete(); // Это установит deleted_at благодаря SoftDeletes

        return response()->json([
            'message' => 'Requisite deleted successfully',
            'deleted_at' => $requisite->deleted_at
        ]);
    }
}
