<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\FinancialLedger;
use App\Enums\OrderStatus;
use App\Jobs\DeliverProductJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReconcileOrdersCommand extends Command
{
    protected $signature = 'orders:reconcile';
    protected $description = 'Сверка финансового журнала и автоматическое восстановление зависших заказов';

    public function handle(): int
    {
        $this->info('=== Запуск скрипта сверки и восстановления ===');
        Log::info('Reconciliation: Начало периодической проверки.');

        $this->recoverHungOrders();
        $this->detectUnpaidDeliveries();
        $this->verifyFinancialLedger();

        $this->info('=== Сверка успешно завершена ===');
        return Command::SUCCESS;
    }

    /**
     * ЭТАП 4: Автоматическое доведение до результата всех зависших и сбойных оплаченных заказов.
     */
    private function recoverHungOrders(): void
    {
        $this->line('1. Проверка зависших и восстановимых оплаченных заказов...');

        /**
         *  обработке заказы, но и восстановимые статусы сбоев (out_of_stock, delivery_failed).
         */
        $hungOrders = Order::whereIn('status', [
            OrderStatus::PAID->value,
            OrderStatus::DELIVERING->value,
            OrderStatus::OUT_OF_STOCK->value,
            OrderStatus::DELIVERY_FAILED->value
        ])
            ->where('updated_at', '<', now()->subMinutes(5))
            ->whereNull('issued_product_code')
            ->get();

        if ($hungOrders->isEmpty()) {
            $this->info('-> Зависших или требующих восстановления заказов не обнаружено.');
            return;
        }

        $this->warn("-> Обнаружено заказов для восстановления: {$hungOrders->count()}. Переотправка в очередь.");

        foreach ($hungOrders as $order) {
            Log::warning("Reconciliation: Заказ {$order->id} (статус: '{$order->status->value}') отправлен на принудительное восстановление выдачи.");

            DeliverProductJob::dispatch($order->id)->onQueue('delivery_processing');
        }
    }

    /**
     * Проверка аномалии: Критический сценарий (Выдан, но не оплачен).
     */
    private function detectUnpaidDeliveries(): void
    {
        $this->line('2. Поиск критических расхождений (Выдан, но не оплачен)...');

        $anomalyOrders = Order::where('status', OrderStatus::DELIVERED->value)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('financial_ledger')
                    ->whereRaw('financial_ledger.order_id = orders.id')
                    ->where('financial_ledger.type', 'deposit');
            })
            ->get();

        if ($anomalyOrders->isNotEmpty()) {
            foreach ($anomalyOrders as $order) {
                $errorMessage = "КРИТИЧЕСКАЯ АНОМАЛИЯ: Заказ {$order->id} имеет статус DELIVERED, но проводка оплаты в финансовом журнале отсутствует!";
                $this->error($errorMessage);
                Log::alert("Reconciliation: {$errorMessage}");
            }
        } else {
            $this->info('-> Аномалий "Выдан, но не оплачен" не обнаружено.');
        }
    }

    /**
     * ЭТАП 4: Математическая сверка журнала двойной записи.
     */
    private function verifyFinancialLedger(): void
    {
        $this->line('3. Математическая сверка баланса денежных движений...');

        // Сумма всех фактических денег, поступивших в систему по Ledger
        $totalDepositsCents = (int) FinancialLedger::where('type', 'deposit')->sum('amount_cents');

        /**
         * Деньги в журнале должны быть равны сумме ВСЕХ заказов, которые прошли успешный вебхук оплаты,
         * включая те, которые прямо сейчас находятся на этапе асинхронной выдачи или в восстановимом сбое.
         */
        $paidStatuses = [
            OrderStatus::PAID->value,
            OrderStatus::DELIVERING->value,
            OrderStatus::DELIVERED->value,
            OrderStatus::OUT_OF_STOCK->value,
            OrderStatus::DELIVERY_FAILED->value
        ];

        $totalPaidOrdersCostCents = (int) Order::whereIn('status', $paidStatuses)->sum('price_cents');

        $difference = $totalDepositsCents - $totalPaidOrdersCostCents;

        if ($difference === 0) {
            $this->info("-> Финансовый журнал идеально сходится копейка в копейку. Общий оборот: " . ($totalDepositsCents / 100) . " руб.");
            Log::info("Reconciliation: Финансовый баланс Ledger полностью сошелся.");
        } else {
            $errorAmount = $difference / 100;
            $errorMessage = "🚨 ФИНАНСОВЫЙ ДИСБАЛАНС! Разница между приходом по Ledger и учтенными оплатами: {$errorAmount} руб.";
            $this->error("-> {$errorMessage}");
            Log::emergency("Reconciliation: {$errorMessage}");
        }
    }
}
