<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Миграция данных: легаси-статус 30 → 16 в payout_requests.
 *
 * 24.12.2025 значением PayoutRequest::STATUS_TICKET_UPLOADED было 30
 * (коммит 0e6764c), на следующий день его перенумеровали в 16 (12ae802).
 * Заявки, созданные в это окно, остались со статусом 30: это то же состояние
 * «чек загружен», но ни settings.json → payout_statuses, ни
 * PayoutRequest::withdrawals() его не знают — такие заявки не попадают в
 * списание, и баланс их партнёров завышен на сумму заявок.
 *
 * Схему не трогаем, меняются только данные.
 */
return new class extends Migration
{
    private const LEGACY_STATUS = 30;
    private const CURRENT_STATUS = 16; // PayoutRequest::STATUS_TICKET_UPLOADED

    public function up(): void
    {
        $rows = DB::table('payout_requests')
            ->where('status', self::LEGACY_STATUS)
            ->orderBy('id')
            ->get(['id', 'user_id', 'withdrawal_amount']);

        if ($rows->isEmpty()) {
            $this->say('payout_requests: заявок со статусом ' . self::LEGACY_STATUS . ' нет, менять нечего.');
            Log::info('migration.fix_legacy_payout_status_30: нечего менять');
            return;
        }

        $ids = $rows->pluck('id')->all();

        $updated = DB::table('payout_requests')
            ->whereIn('id', $ids)
            ->update(['status' => self::CURRENT_STATUS]);

        Log::info('migration.fix_legacy_payout_status_30: статус ' . self::LEGACY_STATUS
            . ' → ' . self::CURRENT_STATUS, [
            'updated' => $updated,
            'ids' => $ids,
            'rows' => $rows->map(fn($row) => [
                'id' => $row->id,
                'user_id' => $row->user_id,
                'withdrawal_amount' => $row->withdrawal_amount,
            ])->all(),
        ]);

        $this->say(sprintf('payout_requests: статус %d → %d, строк: %d, id: %s',
            self::LEGACY_STATUS, self::CURRENT_STATUS, $updated, implode(', ', $ids)));

        foreach ($rows->groupBy('user_id') as $userId => $userRows) {
            $this->say(sprintf('  партнёр #%s: заявок %d на сумму %s — теперь учитываются в балансе',
                $userId, $userRows->count(), number_format((float) $userRows->sum('withdrawal_amount'), 2, '.', ' ')));
        }
    }

    /**
     * Откат не делаем намеренно.
     *
     * Статус 30 — не состояние, а опечатка истории: с 25.12.2025 этого значения
     * нет ни в коде, ни в settings.json, и вернуть его — значит заново завысить
     * баланс этих партнёров. Вслепую откатывать «все 16 → 30» тоже нельзя: под
     * 16 лежат и нормальные заявки. Исходные id записаны в лог
     * (`migration.fix_legacy_payout_status_30`) — если откат всё же
     * понадобится, он делается точечно по этому списку вручную.
     */
    public function down(): void
    {
        // Намеренно пусто — см. комментарий выше.
    }

    private function say(string $line): void
    {
        if (isset($this->output) && $this->output) {
            $this->output->writeln($line);
            return;
        }
        echo $line, PHP_EOL;
    }
};
