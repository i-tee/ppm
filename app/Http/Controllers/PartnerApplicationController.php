<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PartnerApplication;

class PartnerApplicationController extends Controller
{
    public function store(Request $req)
    {
        $validated = $req->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'cooperation_type_id' => 'required|integer',
            'partner_type_id' => 'nullable|integer',
            'company_name' => 'nullable|string|max:255',
            'experience' => 'nullable|string',
            'comment' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'links' => 'nullable|array',
            'links.*' => 'nullable|string',
            // status_id не валидируем, чтобы задавать его серверной логикой
        ]);

        // Очистка и подготовка links
        if (isset($validated['links'])) {
            $validated['links'] = array_values(array_filter($validated['links']));
            if (empty($validated['links'])) {
                $validated['links'] = null; // Для NULL вместо пустого массива
            }
        }

        // Автоматически устанавливаем:
        // - user_id (текущий авторизованный пользователь)
        // - status_id = 0 (новый) если не указан
        $data = array_merge($validated, [
            'user_id' => $req->user()->id,
            'status_id' => $req->input('status_id', 0), // По умолчанию "new"
        ]);

        $app = PartnerApplication::create($data);

        return response()->json([
            'message' => 'Application submitted successfully',
            'data' => $app->load('user') // Опционально: загружаем связанного пользователя
        ], 201);
    }
}
