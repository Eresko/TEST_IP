<?php

namespace App\Services\OrderServices;

use App\Models\Order;
use App\Models\FinancialLedger;
use App\Enums\OrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // <-- ИСПРАВЛЕНО: Импортируем логгер
use Exception;

/**
 * Сервис мгновенной сверки данных для систем мониторинга (Этап 4).
 */
class ReconcileService
{
    /**
     * Запуск процесса аудита и сверки финансовых инвариантов.
     *
     * @return array<string, mixed>
     * @throws Exception
     */
    public function start(): array
    {
        // 1. Аномалия: Деньги взяли, товар не выдали за 5 минут
        $paidNotIssued = Order::whereIn('status', [OrderStatus::PAID->value, OrderStatus::DELIVERING->value])
            ->where('updated_at', '<', now()->subMinutes(5))
            ->whereNull('issued_product_code')
            ->pluck('id');

        // 2. Аномалия: Товар доставлен, но записи о депозите в фин. журнале нет
        $issuedNotPaid = Order::where('status', OrderStatus::DELIVERED->value)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('financial_ledger')
                    ->whereRaw('financial_ledger.order_id = orders.id')
                    ->where('financial_ledger.type', 'deposit');
            })
            ->pluck('id');

        // 3. Математическая сверка журнала денежных движений (Двойная запись)
        $totalDepositsCents = (int) FinancialLedger::where('type', 'deposit')->sum('amount_cents');
        $totalDeliveredCostCents = (int) Order::where('status', OrderStatus::DELIVERED->value)->sum('price_cents');
        $ledgerDifferenceCents = $totalDepositsCents - $totalDeliveredCostCents;

        $isLedgerConsistent = ($ledgerDifferenceCents === 0);

        Log::info('On-demand reconciliation executed', [
            'context' => 'reconciliation_service',
            'consistent' => $isLedgerConsistent,
            'paid_not_issued_count' => $paidNotIssued->count(),
            'issued_not_paid_count' => $issuedNotPaid->count(),
            'difference_rub' => $ledgerDifferenceCents / 100
        ]);

        // ИСПРАВЛЕНИЕ: Возвращаем чистый массив данных, а не HTTP-ответ!
        return [
            'success' => true,
            'timestamp' => now()->toIso8601String(),
            'summary' => [
                'ledger_is_consistent' => $isLedgerConsistent,
                'ledger_difference_rub' => $ledgerDifferenceCents / 100,
            ],
            'anomalies' => [
                'paid_but_not_issued_ids' => $paidNotIssued,
                'issued_but_not_paid_ids' => $issuedNotPaid,
            ]
        ];
    }
}
