<?php

namespace App\Services;

/**
 * Единственная формула баланса партнёра (этап В, PPM-W13).
 *
 * До этого расчёт жил внутри `UserCouponController::buildBusinessData()` и
 * был доступен только по одному партнёру за запрос. Админский список
 * «Партнёры» считает те же цифры по всем партнёрам сразу, и чтобы они не
 * разъезжались с тем, что видит сам партнёр, обе стороны зовут этот метод.
 * Отличается только сборка `$orders`: у партнёра – `JoomlaCoupon::credits()`,
 * у списка – пакетная сборка в `AdminPartnersController`.
 *
 * Порядок операций перенесён из `buildBusinessData()` буквально (в том
 * числе `ceil()` на первом шаге и «неполные» вычитания под `if > 0`) –
 * менять его нельзя, иначе цифры разойдутся со старым кабинетом.
 */
class PartnerBalanceCalculator
{
    /**
     * @param  array|\Traversable  $orders  Оплаченные заказы партнёра (Joomla + новый
     *                                      сайт), у каждого – поле `cashback`.
     * @param  float  $legacyPaymentsDebit    Выплаты старой партнёрки (avicenna_pp_payments).
     * @param  float|null  $oldBalanceSumm    Старый баланс промокода, если он больше нуля.
     * @param  float  $bonusCodesDebit        Стоимость выданных бонус-кодов.
     * @param  float  $payoutRequestsDebit    Заявки на вывод, влияющие на баланс.
     * @param  float  $backendReversalsDebit  Сторно нового сайта.
     * @return array{balance:float,expenseSummary:float,totalAccruals:float,ordersCount:int}
     */
    public static function compute(
        $orders,
        float $legacyPaymentsDebit,
        ?float $oldBalanceSumm,
        float $bonusCodesDebit,
        float $payoutRequestsDebit,
        float $backendReversalsDebit
    ): array {
        $totalAccruals = 0.0;
        $ordersCount = 0;

        foreach ($orders as $order) {
            $ordersCount++;
            // Как в JoomlaCoupon::credits(): отрицательный кешбэк в начисления
            // не идёт (getPpOrders уже применил поправки).
            $cashback = (float) (is_array($order) ? ($order['cashback'] ?? 0) : $order->cashback);
            if ($cashback > 0) {
                $totalAccruals += $cashback;
            }
        }

        $totalAccruals = round($totalAccruals, 2);

        // Далее – порядок из buildBusinessData(), шаг в шаг.
        $expenseSummary = 0;
        $expenseSummary += $legacyPaymentsDebit;

        $balance = ceil($totalAccruals - $legacyPaymentsDebit);

        if ($oldBalanceSumm !== null) {
            $balance += $oldBalanceSumm;
        }

        if ($bonusCodesDebit > 0) {
            $balance -= $bonusCodesDebit;
            $expenseSummary += $bonusCodesDebit;
        }

        $expenseSummary += $payoutRequestsDebit;

        if ($payoutRequestsDebit > 0) {
            $balance -= $payoutRequestsDebit;
        }

        if ($backendReversalsDebit > 0) {
            $expenseSummary += $backendReversalsDebit;
            $balance -= $backendReversalsDebit;
        }

        return [
            'balance' => $balance,
            'expenseSummary' => $expenseSummary,
            'totalAccruals' => $totalAccruals,
            'ordersCount' => $ordersCount,
        ];
    }
}
