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
            'partner_type_id' => 'required|integer|in:1,2,3,4',
            'full_name' => 'sometimes|required|string|max:255',
            'organization_name' => 'nullable|string|max:255',
            'inn' => 'nullable|string|max:20',
            'ogrn' => 'nullable|string|max:20',
            'kpp' => 'nullable|string|max:20',
            'legal_address' => 'nullable|string|max:255',
            'postal_address' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'bik' => 'nullable|string|max:20',
            'correspondent_account' => 'nullable|string|max:20',
            'payment_account' => 'nullable|string|max:20',
            'card_number' => 'nullable|string|max:20',
            'phone_for_sbp' => 'nullable|string|max:20',
            'additional_info' => 'nullable|string',
        ]);

        $data['user_id'] = Auth::id();

        $requisite = new Requisite($data);
        $requisite->validateByType(); // Валидация по типу из модели
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