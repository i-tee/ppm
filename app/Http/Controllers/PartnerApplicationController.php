<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PartnerApplication;

class PartnerApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = PartnerApplication::query();

        // Фильтры
        if ($request->has('status_id')) {
            $query->where('status_id', $request->status_id);
        }
        if ($request->has('cooperation_type_id')) {
            $query->where('cooperation_type_id', $request->cooperation_type_id);
        }
        if ($request->has('partner_type_id')) {
            $query->where('partner_type_id', $request->partner_type_id);
        }

        // Сортировка
        if ($request->has('sort_by')) {
            $query->orderBy($request->sort_by, $request->sort_direction ?? 'asc');
        }

        // Пагинация
        $perPage = $request->per_page ?? 15;
        $applications = $query->paginate($perPage);

        return response()->json($applications);
    }

    public function show($id)
    {
        $application = PartnerApplication::findOrFail($id);
        return response()->json($application);
    }

    public function store(Request $req)
    {
        $validated = $req->validate([
            'last_name' => 'required|string|max:100',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'cooperation_type_id' => 'required|integer',
            'partner_type_id' => 'nullable|integer',
            'company_name' => 'nullable|string|max:255',
            'specialty' => 'nullable|string|max:255',
            'experience_years' => 'nullable|integer|min:0|max:80',
            'comment' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'links' => 'nullable|array',
            'links.*' => 'nullable|string',
        ]);

        if (isset($validated['links'])) {
            $validated['links'] = array_values(array_filter($validated['links']));
            if (empty($validated['links'])) {
                $validated['links'] = null;
            }
        }

        // full_name собирается сервером из фамилии/имени/отчества — с
        // фронта его больше не принимаем.
        $validated['full_name'] = trim(implode(' ', array_filter([
            $validated['last_name'],
            $validated['first_name'],
            $validated['middle_name'] ?? null,
        ], fn ($part) => $part !== null && $part !== '')));

        $data = array_merge($validated, [
            'user_id' => $req->user()->id,
            // Всегда 0 (новая), не из запроса — иначе партнёр мог подать
            // сразу одобренную заявку.
            'status_id' => 0,
        ]);

        $app = PartnerApplication::create($data);

        return response()->json([
            'message' => 'Application submitted successfully',
            'data' => $app->load('user')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $application = PartnerApplication::findOrFail($id);

        $validated = $request->validate([
            'last_name' => 'sometimes|required|string|max:100',
            'first_name' => 'sometimes|required|string|max:100',
            'middle_name' => 'sometimes|nullable|string|max:100',
            'phone' => 'sometimes|string|max:20',
            'email' => 'sometimes|nullable|email|max:255',
            'cooperation_type_id' => 'sometimes|integer',
            'partner_type_id' => 'sometimes|nullable|integer',
            'status_id' => 'sometimes|integer',
            'company_name' => 'sometimes|nullable|string|max:255',
            // Старое поле "опыт" (смесь специальности/опыта из старых анкет)
            // не трогаем при правке новых полей - оставляем как есть.
            'experience' => 'sometimes|nullable|string',
            'specialty' => 'sometimes|nullable|string|max:255',
            'experience_years' => 'sometimes|nullable|integer|min:0|max:80',
            'comment' => 'sometimes|nullable|string',
            'city' => 'sometimes|nullable|string|max:255',
            'links' => 'sometimes|nullable|array',
            'links.*' => 'sometimes|nullable|string',
        ]);

        // Очистка ссылок
        if (array_key_exists('links', $validated)) {
            if (is_array($validated['links'])) {
                $validated['links'] = array_values(array_filter($validated['links']));
                if (empty($validated['links'])) {
                    $validated['links'] = null;
                }
            } else {
                $validated['links'] = null;
            }
        }

        // full_name пересобираем только если правили одну из частей имени -
        // иначе не трогаем (у старых заявок частей имени вообще нет).
        if (array_key_exists('last_name', $validated) || array_key_exists('first_name', $validated) || array_key_exists('middle_name', $validated)) {
            $lastName = $validated['last_name'] ?? $application->last_name;
            $firstName = $validated['first_name'] ?? $application->first_name;
            $middleName = array_key_exists('middle_name', $validated) ? $validated['middle_name'] : $application->middle_name;

            $validated['full_name'] = trim(implode(' ', array_filter(
                [$lastName, $firstName, $middleName],
                fn ($part) => $part !== null && $part !== ''
            )));
        }

        $application->update($validated);

        return response()->json([
            'message' => 'Application updated successfully',
            'data' => $application
        ]);
    }

    public function destroy($id)
    {
        $application = PartnerApplication::findOrFail($id);
        $application->delete();

        return response()->json([
            'message' => 'Application deleted successfully'
        ]);
    }
}
