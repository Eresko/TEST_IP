<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EmulatePaymentCommand extends Command
{
    // 1. Сначала обнови сигнатуру команды вверху класса (убираем обязательный order_id):
    protected $signature = 'payment:emulate {--race : Запустить стресс-тест из 50 параллельных вебхуков}';

// 2. Полностью замени метод handle():
    public function handle(): int
    {
        // ИСПРАВЛЕНИЕ: Внутри Docker-сети шлем запросы строго на имя Nginx-сервиса
        $baseUrl = "http://test_nginx";

        $this->info("Шаг 1: Автоматическое создание нового заказа через API...");
        
        $orderKey = 'order_auto_key_' . bin2hex(random_bytes(4));

        $orderResponse = Http::withHeaders([
            'Idempotency-Key' => $orderKey,
            'Accept'          => 'application/json'
        ])->post("{$baseUrl}/api/v1/orders", [
            'sku' => 'KEY-GTA5'
        ]);

        if (!$orderResponse->successful()) {
            $this->error("❌ Не удалось создать заказ через API. Ответ: " . $orderResponse->body());
            return Command::FAILURE;
        }

        $orderId = $orderResponse->json('data.order_id');
        $priceCents = $orderResponse->json('data.price_cents') ?? 10000;

        $this->info("✅ Заказ успешно создан. ID: {$orderId}");
        $this->line("--------------------------------------------------");
        
        $eventId = 'evt_' . bin2hex(random_bytes(6));
        $amountInRub = $priceCents / 100;

        $payload = [
            "event_id"   => $eventId,
            "order_id"   => $orderId,
            "status"     => "paid",
            "amount"     => $amountInRub,
            "currency"   => "RUB",
            "created_at" => now()->toIso8601String()
        ];

        $headers = [
            'Idempotency-Key' => $eventId,
            'Accept'          => 'application/json'
        ];

        $webhookUrl = "{$baseUrl}/api/v1/payments/webhook";

        /**
         * Сценарий А: Проверка гонок 50 запросов
         */
        if ($this->option('race')) {
            $this->warn("Шаг 2: Запуск состязательного сценария (50 параллельных вебхуков)...");

            $responses = Http::pool(function ($pool) use ($webhookUrl, $payload, $headers) {
                $requests = [];
                for ($i = 0; $i < 50; $i++) {
                    $requests[] = $pool->withHeaders($headers)->post($webhookUrl, $payload);
                }
                return $requests;
            });

            $successCount = 0;
            $errorCount = 0;

            foreach ($responses as $response) {
                if ($response->status() === 200 || $response->status() === 201) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            }

            // Даем небольшую паузу на фиксацию данных веб-сервером
            usleep(300000);

            // Проверяем проводки в финансовом журнале
            $ledgerCount = DB::table('financial_ledger')->where('order_id', $orderId)->count();
            $primaryProcessed = ($ledgerCount === 1) ? 1 : 0;
            $idempotentCount = $successCount - $primaryProcessed;

            $this->info("\n=== АНАЛИТИКА ТЕСТА ГОНКИ ===");
            $this->line("Всего отправлено запросов: 50");
            $this->info("Первичная успешная обработка (Фиксация оплаты): {$primaryProcessed}");
            $this->comment("Идемпотентные отскоки (Защита от дублей): {$idempotentCount}");

            if ($errorCount > 0) {
                $this->error("Системные ошибки (500 / 4xx): {$errorCount}");
            }

            $this->line("Количество созданных проводок в финансовом журнале: {$ledgerCount}");

            if ($ledgerCount === 1 && $errorCount === 0) {
                $this->info("🏆 ТЕСТ УСПЕШНО ПРОЙДЕН: Критерий Exactly-Once выполнен. Деньги зафиксированы ровно один раз!");
                return Command::SUCCESS;
            } else {
                $this->error("❌ ТЕСТ ПРОВАЛЕН: Нарушена консистентность данных.");
                return Command::FAILURE;
            }
        }

        /**
         * Сценарий Б: Одиночный вебхук оплаты
         */
        $this->info("Шаг 2: Отправка одиночного вебхука оплаты для заказа...");
        $response = Http::withHeaders($headers)->post($webhookUrl, $payload);

        if ($response->successful()) {
            $this->info("🎉 Успешно! Сервер обработал платеж: 200 OK.");
            $this->line("Ответ бэкенда: " . $response->body());
            return Command::SUCCESS;
        }

        $this->error("❌ Ошибка при обработке вебхука. Код: " . $response->status());
        $this->error($response->body());
        return Command::FAILURE;
    }

}
