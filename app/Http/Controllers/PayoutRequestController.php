<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use App\Models\JoomlaCoupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Partners;
use App\Models\Requisite;
use App\Models\User;
use App\Http\Controllers\UserCouponController;
use Illuminate\Http\JsonResponse;

/**
 * Контроллер для управления заявками на вывод средств (PayoutRequest).
 * Обрабатывает CRUD-операции для агентов и админов.
 * Все методы защищены middleware 'auth:sanctum'.
 */
class PayoutRequestController extends Controller
{
    /**
     * Отображает список активных заявок агента с пагинацией.
     * Использует scopes модели для фильтрации по пользователю и активности.
     * Загружает связанные модели (user, approver, requisite) для деталей.
     * Добавляет accessor status_text для отображения статуса.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $userId = Auth::id(); // ID текущего авторизованного агента

        $payoutRequests = PayoutRequest::active()  // Только активные заявки (is_active = true)
            ->byUser($userId)  // Только заявки этого агента (user_id = $userId)
            ->with(['user', 'approver', 'requisite']) // Загружаем связанные модели для избежания N+1
            ->append('status_text') // Добавляем вычисляемый атрибут текста статуса (accessor)
            ->orderBy('created_at', 'desc')  // Сортировка по дате создания (новые сверху)
            ->paginate(20); // Пагинация по 20 записей (для Vue DataTable)

        return response()->json([
            'success' => true,
            'data' => $payoutRequests,  // Пагинированный результат (data, current_page, etc.)
            'message' => trans('payoutRequest.list.success'), // Локализованное сообщение успеха
        ]);
    }

    /**
     * Отображает детали конкретной заявки.
     * Проверяет, что заявка принадлежит агенту и активна.
     * Загружает связанные модели для полного отображения.
     * Добавляет accessor status_text для статуса.
     *
     * @param PayoutRequest $payoutRequest
     * @return JsonResponse
     */
    public function show(PayoutRequest $payoutRequest)
    {
        // Проверяем права доступа: заявка должна быть активной и принадлежать агенту
        if ($payoutRequest->user_id !== Auth::id() || !$payoutRequest->is_active) {
            abort(404, trans('payoutRequest.not_found')); // 404 с локализованным сообщением
        }

        $payoutRequest->load(['user', 'approver', 'requisite']) // Загружаем связанные модели
            ->append('status_text'); // Добавляем вычисляемый атрибут текста статуса

        return response()->json([
            'success' => true,
            'data' => $payoutRequest,  // Полные детали заявки с отношениями
            'message' => trans('payoutRequest.show.success'), // Локализованное сообщение успеха
        ]);
    }

    /**
     * Создаёт новую заявку на вывод средств.
     * Вычисляет баланс агента через UserCouponController::data() (сумма из Joomla + payouts + bonus).
     * Валидирует сумму (не больше баланса), реквизит (принадлежит агенту).
     * Рассчитывает комиссию по tax из settings.json (partner_types).
     * Устанавливает дефолты: status=0 (created), approver_id=null, is_active=true.
     * Округляет суммы к целому числу (round() как в JS Math.round()).
     * Принимает received_amount/commission_amount от фронта (если присланы), иначе рассчитывает.
     * Сохраняет в БД и возвращает с загруженным реквизитом и status_text.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            // Получаем полный баланс агента через UserCouponController::data() (актуальный расчёт)
            $userCouponController = new UserCouponController();
            $balanceResponse = $userCouponController->data($request);  // Вызов метода data()
            $balanceData = $balanceResponse->getData(true);  // Извлекаем данные из JsonResponse как array
            $balance = (float) ($balanceData['balance'] ?? 0);  // Баланс (сумма всех источников)

            $validated = $request->validate([
                'withdrawal_amount' => [
                    'required',
                    'numeric',
                    'min:0.01',
                    'max:' . $balance,  // Сумма не больше доступного баланса
                ],
                'requisite_id' => 'required|integer|exists:requisites,id',
                'note' => 'nullable|string|max:1000',
                'received_amount' => 'nullable|numeric|min:0',  // Фактическая сумма (от фронта или рассчитанная)
                'commission_percentage' => 'nullable|numeric|min:0|max:100',  // % комиссии (от фронта или рассчитанная)
                'commission_amount' => 'nullable|numeric|min:0',  // Сумма комиссии (от фронта или рассчитанная)
            ], [
                'withdrawal_amount.max' => trans('payoutRequest.validate.max_amount'),  // Кастомное сообщение для превышения баланса
            ]);

            // Проверяем, что реквизит принадлежит агенту (дополнительная валидация)
            $requisite = Requisite::where('id', $validated['requisite_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            $partnerTypeId = $requisite->partner_type_id;

            // Вычисляем комиссию по tax из settings.json, если фронт не прислал
            $partnerTypes = Partners::getSettings('partner_types');
            $commissionPercentage = $validated['commission_percentage'] ?? (collect($partnerTypes)->firstWhere('id', $partnerTypeId)['tax'] ?? 0);
            $commissionAmount = $validated['commission_amount'] ?? round($validated['withdrawal_amount'] * $commissionPercentage / 100);  // Округление к целому
            $receivedAmount = $validated['received_amount'] ?? round($validated['withdrawal_amount'] - $commissionAmount);  // Округление к целому

            // Округляем все суммы к целому числу (как JS Math.round())
            $validated['withdrawal_amount'] = round($validated['withdrawal_amount']);
            $validated['commission_percentage'] = $commissionPercentage;
            $validated['commission_amount'] = $commissionAmount;
            $validated['received_amount'] = $receivedAmount;
            $validated['status'] = PayoutRequest::STATUS_CREATED; // 0 - создана
            $validated['user_id'] = $user->id;  // ID агента-инициатора
            $validated['approver_id'] = null;  // ID одобряющего (заполнит админ)
            $validated['is_active'] = true;  // Активна (мягкое удаление не применено)

            $payoutRequest = PayoutRequest::create($validated);  // Сохраняем в БД

            // Загружаем реквизит и вычисляемый атрибут status_text для ответа
            $payoutRequest->load('requisite');
            $payoutRequest->status_text = $payoutRequest->getStatusTextAttribute();

            return response()->json([
                'success' => true,
                'data' => $payoutRequest,  // Заявка с отношениями и status_text
                'message' => trans('payoutRequest.create.success'), // Локализованное сообщение успеха
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Обработка ошибок валидации (422 Unprocessable Entity)
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Обработка неожиданных ошибок (500 Internal Server Error)
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Обновляет существующую заявку (одобрение/выплата/отмена).
     * TODO: Реализовать для админа — смена status, approver_id, корректировка сумм, уведомления.
     *
     * @param Request $request
     * @param PayoutRequest $payoutRequest
     * @return JsonResponse
     */
    public function update(Request $request, PayoutRequest $payoutRequest)
    {
        // TODO: Обновление (одобрение/выплата)
    }

    /**
     * Деактивирует заявку (мягкое удаление: is_active=false, status=99 deleted).
     * TODO: Реализовать для админа — проверка прав, уведомление агента.
     *
     * @param PayoutRequest $payoutRequest
     * @return JsonResponse
     */
    public function destroy(PayoutRequest $payoutRequest)
    {
        // TODO: Деактивация (is_active = false, status=99)
    }

    /** Это раздел методов для Админа **/

    public function adminIndex(Request $request)
    {
        $query = PayoutRequest::active();

        // Фильтр по статусу (если передан)
        $statusId = $request->get('status_id');
        if ($statusId !== null) {
            $query->where('status', $statusId);
        }

        // Получаем данные с пагинацией
        $payoutRequests = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 100));

        return response()->json([
            'success' => true,
            'data' => $payoutRequests,
            'message' => trans('payoutRequest.admin_list.success'),
        ]);
    }
    /** Это раздел методов для Админа **/

    public function adminIndexPrepared(Request $request)
    {
        abort_unless(
            auth()->user()->hasAccessLevel(1) ||
                auth()->user()->hasAccessLevel(2),
            403,
            'Permission denied – no access rights'
        );

        $query = PayoutRequest::active()
            ->with(['user', 'requisite']);   // <-- жадная загрузка

        if ($request->filled('status_id')) {
            $query->where('status', $request->status_id);
        }

        $payoutRequests = $query
            ->latest()
            ->paginate($request->get('per_page', 100));

        $partnerTypes = $partnerTypes = Partners::getSettings('partner_types');

        /*  теперь в каждом элементе коллекции уже есть
        $payout->user  и  $payout->requisite
        можно сразу вернуть коллекцию, а можно
        отдать только нужные поля через API-ресурс */
        return response()->json([
            'success' => true,
            'data'    => $payoutRequests,
            'partnerTypes'    => $partnerTypes,
            'message' => trans('payoutRequest.admin_list.success'),
        ]);
    }

    /**
     * Обновляет заявку на вывод: подтверждает выплату (status=PAID).
     * Устанавливает approver_id (ID текущего админа), proof_link (ссылка на чек),
     * note (комментарий). Только для админов (уровни 1-2).
     * Требует: proof_link (строка), note (опционально, max:1000).
     *
     * @param Request $request
     * @param PayoutRequest $payoutRequest
     * @return JsonResponse
     */
    public function adminReceived(Request $request, PayoutRequest $payoutRequest)
    {
        // Проверяем права: только админы (уровни 1 или 2)
        abort_unless(
            auth()->user()->hasAccessLevel(1) || auth()->user()->hasAccessLevel(2),
            403,
            trans('payoutRequest.permission_denied') // Добавь ключ в локализацию, если нет
        );

        $validated = $request->validate([
            'proof_link' => 'required|url|max:500', // Ссылка на чек (URL, max 500 символов)
            'note' => 'nullable|string|max:1000', // Комментарий (опционально)
        ]);

        try {
            // Обновляем запись
            $payoutRequest->update([
                'status' => PayoutRequest::STATUS_PAID,
                'approver_id' => Auth::id(), // ID текущего админа
                'proof_link' => $validated['proof_link'],
                'note' => $validated['note'] ?? null,
            ]);

            // Перезагружаем с отношениями для ответа
            $payoutRequest->load(['user', 'approver', 'requisite']);
            $payoutRequest->append('status_text');

            return response()->json([
                'success' => true,
                'data' => $payoutRequest,
                'message' => trans('payoutRequest.received.success'), // Добавь ключ: "Выплата подтверждена"
            ], 200);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => trans('payoutRequest.validate.failed'), // "Ошибка валидации"
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => trans('payoutRequest.error.internal'), // "Внутренняя ошибка сервера"
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
