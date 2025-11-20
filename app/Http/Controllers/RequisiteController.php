<?php

namespace App\Http\Controllers;

use App\Models\Requisite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Helpers\Requisites; // <-- крутой хелпер
use App\Helpers\Partners;
use Illuminate\Support\Facades\Validator;

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

    public function store(Request $request)
    {
        $partnerTypeId = $request->integer('partner_type_id');

        if (!$partnerTypeId) {
            abort(422, __('requisites.validation_failed'));
        }

        $rules = Requisites::getValidationRules($partnerTypeId);

        // Разрешённые типы партнёров
        $allowedTypes = collect(Partners::getSettings('partner_types') ?? [])
            ->pluck('id')
            ->filter()
            ->values()
            ->toArray() ?: [1, 2, 3, 4];

        $rules['partner_type_id'] = ['required', 'integer', 'in:' . implode(',', $allowedTypes)];

        // Человекочитаемые названия полей — берём label из конфига и переводим правильно
        $attributes = collect(Requisites::getFieldsForPartnerType($partnerTypeId))
            ->pluck('label', 'name')  // ключ — name поля, значение — label из конфига ('requisites.birth_date')
            ->map(fn($label) => __($label))  // переводим каждый label
            ->toArray();

        $attributes['partner_type_id'] = __('requisites.partner_type');

        // Сообщения об ошибках — только через __()
        $messages = [
            'partner_type_id.in' => __('requisites.invalid_partner_type'),

            'required' => __('requisites.required'), // Поле «:label» обязательно для заполнения.
            'email'    => __('requisites.email'),
            'numeric'  => __('requisites.numeric'),
            'date'     => __('requisites.date'),
            'regex'    => __('requisites.regex'),
        ];

        // Если в конфиге поля есть validation_message — тоже через __()
        foreach (Requisites::getFieldsForPartnerType($partnerTypeId) as $field) {
            if (!empty($field['validation_message'])) {
                $messages["{$field['name']}.regex"] = __($field['validation_message']);
            }
        }

        $validator = Validator::make($request->all(), $rules, $messages, $attributes);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $cleanData = Requisites::filterDataForPartnerType($request->all(), $partnerTypeId);
        $cleanData['user_id'] = Auth::id();
        $cleanData['partner_type_id'] = $partnerTypeId;

        $requisite = Requisite::create($cleanData);

        return response()->json($requisite, 201);
    }

    /**
     * Удалить реквизиты (мягкое удаление через SoftDeletes).
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $isAdmin = $user->hasAccessLevel(1) || $user->hasAccessLevel(2);

        $query = Requisite::query();

        // Если не админ — жёстко фильтруем по своему user_id
        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        }

        // Теперь ищем с учётом фильтра (или без, если админ)
        $requisite = $query->findOrFail($id);

        // Деактивация + мягкое удаление
        $requisite->is_active = false;
        $requisite->save();
        $requisite->delete();

        return response()->json([
            'message' => 'Requisite deleted successfully',
            'deleted_at' => $requisite->deleted_at
        ]);
    }
}
