<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Partners;

class PayoutRequest extends Model
{
    use HasFactory;

    // Константы статусов (из settings.json: cooperation_types[1].payout_statuses)
    const STATUS_CREATED = 0;     // created
    const STATUS_APPROVED = 10;   // approved
    const STATUS_PAID = 20;       // paid
    const STATUS_CANCELLED = 50;  // cancelled
    const STATUS_DELETED = 99;    // deleted (для деактивации + is_active=false)

    /**
     * Accessor для текста статуса (из settings.json).
     */
    public function getStatusTextAttribute()
    {
        $statuses = Partners::getSettings('cooperation_types.1.payout_statuses');
        $statusKey = $statuses[$this->status] ?? 'unknown';
        return trans('payoutRequest.status.' . $statusKey); // Fallback на i18n
    }

    protected $fillable = [
        'user_id',
        'approver_id',
        'requisite_id',
        'withdrawal_amount',
        'received_amount',
        'commission_percentage',
        'commission_amount',
        'status',
        'note',
        'proof_link',
        'is_active',
    ];

    protected $casts = [
        'withdrawal_amount' => 'decimal:2',
        'received_amount' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'status' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Отношения (по ID, без FK в БД — Laravel потянет)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function requisite(): BelongsTo
    {
        return $this->belongsTo(Requisite::class, 'requisite_id');
    }

    // Scopes для удобства в контроллерах/запросах
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_CREATED);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Получает данные о выводах для агента (аналогично withdrawals() в Joomla).
     *
     * @return array
     */
    public static function withdrawals()
    {
        $userId = Auth::id();

        $payoutRequests = self::active()
            ->byUser($userId)
            ->with('requisite') // Для таблицы: requisite.bank_name и т.д.
            ->orderBy('created_at', 'desc')
            ->get();

        // Только created (0) в балансе; approved (10) уже списано
        $debit = $payoutRequests->where('status', self::STATUS_CREATED)->sum('withdrawal_amount');

        return [
            'debit' => $debit,
            'payoutRequests' => $payoutRequests->toArray(), // Массив для json
        ];
    }
}