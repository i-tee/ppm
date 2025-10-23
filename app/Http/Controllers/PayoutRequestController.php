<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Partners;
use App\Models\Requisite;

class PayoutRequestController extends Controller
{
    /**
     * Display a listing of the resource (активные заявки агента с пагинацией).
     */
    public function index(Request $request)
    {
        $userId = Auth::id(); // ID текущего агента

        $payoutRequests = PayoutRequest::active()
            ->byUser($userId)
            ->with(['user', 'approver', 'requisite']) // Загружаем отношения для деталей
            ->append('status_text') // Добавляем текст статуса из accessor
            ->orderBy('created_at', 'desc')
            ->paginate(20); // Как в Vue (perPage=20)

        return response()->json([
            'success' => true,
            'data' => $payoutRequests,
            'message' => trans('payoutRequest.list.success'), // 'Заявки загружены'
        ]);
    }

    /**
     * Display the specified resource (детали заявки).
     */
    public function show(PayoutRequest $payoutRequest)
    {
        // Проверяем, что заявка принадлежит агенту и активна
        if ($payoutRequest->user_id !== Auth::id() || !$payoutRequest->is_active) {
            abort(404, trans('payoutRequest.not_found')); // 'Заявка не найдена'
        }

        $payoutRequest->load(['user', 'approver', 'requisite']) // Загружаем для модалки
            ->append('status_text'); // Добавляем текст статуса

        return response()->json([
            'success' => true,
            'data' => $payoutRequest,
            'message' => trans('payoutRequest.show.success'), // 'Детали заявки загружены'
        ]);
    }

    /**
     * Store a newly created resource in storage (создание заявки).
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'withdrawal_amount' => 'required|numeric|min:0.01|max:' . $user->balance, // Max = баланс агента
            'requisite_id' => 'required|integer|exists:requisites,id|where:user_id,' . $user->id, // Только свои реквизиты
            'note' => 'nullable|string|max:1000',
        ]);

        // Получаем реквизит для partner_type_id
        $requisite = Requisite::findOrFail($validated['requisite_id']);
        $partnerTypeId = $requisite->partner_type_id;

        // Тянем partner_types из settings.json через хелпер
        $partnerTypes = Partners::getSettings('partner_types');
        $commissionPercentage = collect($partnerTypes)->firstWhere('id', $partnerTypeId)['tax'] ?? 0; // Fallback 0%

        $validated['commission_percentage'] = $commissionPercentage;
        $validated['commission_amount'] = $validated['withdrawal_amount'] * $commissionPercentage / 100;
        $validated['received_amount'] = null; // Заполним при paid (status=20)
        $validated['status'] = PayoutRequest::STATUS_CREATED; // 0 из констант модели
        $validated['user_id'] = $user->id;
        $validated['is_active'] = true;

        $payoutRequest = PayoutRequest::create($validated);

        // Списание из баланса? (заморозка — если есть frozen_balance в User, обнови; пока просто логируем)
        // $user->decrement('balance', $validated['withdrawal_amount']); // Если сразу списывать

        $payoutRequest->load('requisite')
            ->append('status_text'); // Добавляем текст статуса в ответ

        return response()->json([
            'success' => true,
            'data' => $payoutRequest,
            'message' => trans('payoutRequest.create.success'), // 'Заявка создана'
        ], 201);
    }

    public function update(Request $request, PayoutRequest $payoutRequest)
    {
        // TODO: Обновление (одобрение/выплата)
    }

    public function destroy(PayoutRequest $payoutRequest)
    {
        // TODO: Деактивация (is_active = false, status=99)
    }
}