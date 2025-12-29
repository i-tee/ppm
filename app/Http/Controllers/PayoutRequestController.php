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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\PayoutPaidNotification;
use App\Notifications\PayoutPaidToCompanyNotification;
use App\Notifications\PayoutTickedUploadToCompanyNotification;
use App\Notifications\PayoutTicketReminderNotification;
use App\Helpers\ErrorNotifier; // Для ошибок
use Illuminate\Support\Arr; // Для Arr::get

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

            // Проверяем, есть ли незавершённая заявка с ожиданием чека
            if ($user->hasPendingTicketPayout()) {
                $pending = $user->pendingTicketPayout();

                return response()->json([
                    'success' => false,
                    'message' => trans('payoutRequest.pending_ticket_required'), // новый ключ в локализации
                    'pending_payout_id' => $pending->id, // чтобы фронт мог подсветить/открыть модалку
                    'pending_payout' => $pending, // чтобы фронт мог подсветить/открыть модалку
                ], 422);
            }

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
            ->with(['user', 'requisite'])
            ->whereIn('status', [
                PayoutRequest::STATUS_CREATED,
                PayoutRequest::STATUS_PAID_WHAIT_TICKET,
                PayoutRequest::STATUS_TICKET_UPLOADED,
            ]);

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
    public function adminReceived(Request $request, $id)
    {
        // Проверяем права: только админы (уровни 1 или 2)
        abort_unless(
            auth()->user()->hasAccessLevel(1) || auth()->user()->hasAccessLevel(2),
            403,
            trans('payoutRequest.permission_denied') // Добавь ключ в локализацию, если нет
        );

        $payoutRequest = PayoutRequest::findOrFail($id);  // Кидает 404, если не найдено

        // Лог для дебага: что пришло в запросе
        Log::info('Payout received request payout', [
            'payout_id' => $payoutRequest->id,
            'approver_id' => Auth::id(),
            'input_data' => $request->all()
        ]);

        $validated = $request->validate([
            'proof_link' => 'required|url|max:500', // Ссылка на чек (URL, max 500 символов)
            'note' => 'nullable|string|max:1000', // Комментарий (опционально)
        ]);

        // Определяем статус в зависимости от типа партнёра
        $isSelfEmployed = ($payoutRequest->requisite->partner_type_id == 2); // самозанятый
        $newStatus = $isSelfEmployed
            ? PayoutRequest::STATUS_PAID_WHAIT_TICKET // 14 — ждём чек от пользователя
            : PayoutRequest::STATUS_PAID;

        try {
            // Лог для дебага: валидированные данные
            Log::info('Validated payout data', [
                'payout_id' => $payoutRequest->id,
                'proof_link' => $validated['proof_link'],
                'note' => $validated['note'] ?? null
            ]);

            // Обновляем запись
            $payoutRequest->update([
                'status' => $newStatus,
                'approver_id' => Auth::id(), // ID текущего админа
                'proof_link' => $validated['proof_link'],
                'note' => $validated['note'] ?? null,
            ]);

            // Лог для дебага: обновление прошло
            Log::info('Payout updated successfully payout', ['payout_id' => $payoutRequest->id]);

            // Перезагружаем с отношениями для ответа
            $payoutRequest->load(['user', 'approver', 'requisite']);
            $payoutRequest->append('status_text');

            // Отправляем уведомления юзеру и компании
            $this->sendPayoutPaidNotifications($payoutRequest);
            Notification::send($payoutRequest->user, new PayoutTicketReminderNotification($payoutRequest));

            return response()->json([
                'success' => true,
                'data' => $payoutRequest,
                'message' => trans('payoutRequest.received_success'), // Добавь ключ: "Выплата подтверждена"
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed for payout', [
                'payout_id' => $payoutRequest->id,
                'errors' => $e->errors()
            ]);
            return response()->json([
                'success' => false,
                'message' => trans('payoutRequest.validate.failed'), // "Ошибка валидации"
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Exception in payout received', [
                'payout_id' => $payoutRequest->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => trans('payoutRequest.error.internal'), // "Внутренняя ошибка сервера"
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Метод: Отправляет уведомления о успешной выплате юзеру и компании
     */
    protected function sendPayoutPaidNotifications(PayoutRequest $payoutRequest): void
    {
        try {
            Log::info("[PayoutRequest] Начало отправки уведомлений о успешной выплате для заявки {$payoutRequest->id}.");

            $user = User::find($payoutRequest->user_id); // Поиск по ID, как просил

            if (! $user) {
                ErrorNotifier::notify('Не найден пользователь для отправки уведомления о успешной выплате');
                Log::error("[PayoutRequest] User не найден для уведомления о выплате заявки {$payoutRequest->id}.");
                return;
            }

            // Уведомление юзеру (send с моделью User — для $notifiable->name в toMail)
            Notification::send($user, new PayoutPaidNotification($payoutRequest));
            Log::info("[PayoutRequest] Уведомление юзеру успешно отправлено для заявки {$payoutRequest->id}.");

            // Уведомление компании (route для email, toMail не использует $notifiable->name)
            $globalSettings = Partners::getSettings('global');
            $email = Arr::get($globalSettings, 'responsible_partnersmail');

            if (!$email) {
                $email = Arr::get($globalSettings, 'global.responsible_partnersmail');
            }

            if (!$email) {
                ErrorNotifier::notify('Почта компании не найдена для уведомления о успешной выплате');
                Log::error("[PayoutRequest] Email компании не найден для заявки {$payoutRequest->id}.");
                return;
            }

            Notification::route('mail', $email)
                ->notify(new PayoutPaidToCompanyNotification($payoutRequest));

            Log::info("[PayoutRequest] Уведомление компании успешно отправлено на $email для заявки {$payoutRequest->id}.");
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            ErrorNotifier::notify('Ошибка при отправке уведомлений о успешной выплате: ' . $errorMsg);
            Log::error("[PayoutRequest] Исключение при отправке уведомлений о выплате заявки {$payoutRequest->id}: $errorMsg. Stack: " . $e->getTraceAsString());
        }
    }

    public function uploadTicket(Request $request, $id)
    {
        $payoutRequest = PayoutRequest::where('user_id', Auth::id())->findOrFail($id);

        // Теперь только если статус именно "ожидание чека"
        if ($payoutRequest->status !== PayoutRequest::STATUS_PAID_WHAIT_TICKET) {
            return response()->json([
                'success' => false,
                'message' => trans('payoutRequest.ticket.invalid_status'),
            ], 403);
        }

        if ($payoutRequest->ticket_proof) {
            return response()->json([
                'success' => false,
                'message' => trans('payoutRequest.ticket.already_uploaded'),
            ], 403);
        }

        $validated = $request->validate([
            'ticket' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240', // до 10 МБ
        ]);

        // Сохраняем в storage/app/public/payout_tickets/{payout_id}
        $path = $request->file('ticket')->store('payout_tickets/' . $payoutRequest->id, 'public');

        $payoutRequest->update([
            'ticket_proof' => $path,
            'status' => PayoutRequest::STATUS_TICKET_UPLOADED,
        ]);


        // Отправляем уведомление компании о загрузке чека
        try {
            Log::info("[PayoutRequest] Пользователь загрузил чек для заявки {$payoutRequest->id}.");

            $globalSettings = Partners::getSettings('global');
            $email = Arr::get($globalSettings, 'responsible_partnersmail');

            if (!$email) {
                $email = Arr::get($globalSettings, 'global.responsible_partnersmail');
            }

            if (!$email) {
                ErrorNotifier::notify('Почта компании не найдена для уведомления о загрузке чека');
                Log::error("[PayoutRequest] Email компании не найден для заявки {$payoutRequest->id}.");
            } else {
                Notification::route('mail', $email)
                    ->notify(new PayoutTickedUploadToCompanyNotification($payoutRequest));

                Log::info("[PayoutRequest] Уведомление о загрузке чека отправлено на $email для заявки {$payoutRequest->id}.");
            }
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            ErrorNotifier::notify('Ошибка при отправке уведомления о загрузке чека: ' . $errorMsg);
            Log::error("[PayoutRequest] Исключение при уведомлении о чеке заявки {$payoutRequest->id}: $errorMsg.");
        }

        $payoutRequest->load(['user', 'approver', 'requisite'])->append('status_text');

        return response()->json([
            'success' => true,
            'data' => $payoutRequest,
            'message' => trans('payoutRequest.ticket.upload_success'),
        ]);
    }

    /**
     * Отправляет напоминание юзеру о загрузке чека после выплаты.
     * Только для админов (уровни 1-2).
     * POST /admin/payout-ticked-reminder/{id}
     *
     * @param int $id ID PayoutRequest
     * @return JsonResponse
     */
    public function adminTicketReminder($id)
    {
        abort_unless(
            auth()->user()->hasAccessLevel(1) || auth()->user()->hasAccessLevel(2),
            403,
            trans('payoutRequest.permission_denied')
        );

        $payoutRequest = PayoutRequest::findOrFail($id);

        // Проверяем статус: только если ждём чек (PAID_WHAIT_TICKET), но чека нет
        if (!in_array($payoutRequest->status, [PayoutRequest::STATUS_PAID_WHAIT_TICKET]) || $payoutRequest->ticket_proof) {
            return response()->json([
                'success' => false,
                'message' => 'Напоминание не требуется: статус не подходит или чек уже загружен',
            ], 422);
        }

        $user = $payoutRequest->user;

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Пользователь не найден',
            ], 404);
        }

        try {
            Notification::send($user, new PayoutTicketReminderNotification($payoutRequest));

            Log::info("[PayoutRequest] Напоминание о загрузке чека отправлено юзеру для заявки {$payoutRequest->id}.");

            return response()->json([
                'success' => true,
                'message' => 'Напоминание успешно отправлено',
            ]);
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            ErrorNotifier::notify('Ошибка при отправке напоминания о загрузке чека: ' . $errorMsg);
            Log::error("[PayoutRequest] Исключение при напоминании о чеке заявки {$payoutRequest->id}: $errorMsg.");

            return response()->json([
                'success' => false,
                'message' => 'Ошибка отправки напоминания',
            ], 500);
        }
    }
}
