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
     * Создать новые реквизиты для пользователя.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'partner_type_id' => 'required|integer',
            'inn' => 'nullable|string|max:20'
        ]);

        $data['user_id'] = Auth::id();

        $requisite = new Requisite($data);
        // $requisite->validateByType(); // Валидация по типу из модели
        $requisite->save();

        return response()->json($requisite, 201);
    }

    /**
     * Удалить реквизиты (только если принадлежат пользователю).
     */
    public function destroy($id)
    {
        $requisite = Requisite::findOrFail($id);
        if ($requisite->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $requisite->delete();
        return response()->json(['message' => 'Requisite deleted']);
    }
}