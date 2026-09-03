<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Exceptions\BusinessException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class Order
 *
 * @property string $id Уникальный идентификатор заказа
 * @property string $sku Артикул цифрового товара
 * @property OrderStatus $status Статус заказа (управляется конечным автоматом)
 * @property int $price_cents Стоимость в копейках/центах
 * @property string|null $supplier_id ID поставщика, выдавшего товар
 * @property string|null $issued_product_code Выданный цифровой ключ или ссылка
 * @property string|null $payment_idempotency_key Ключ идемпотентности платежной системы
 * @property string|null $idempotency_key Защита от дублей при создании запроса
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Collection|FinancialLedger[] $ledgerEntries Записи в финансовом журнале
 */
class Order extends Model
{
    /**
     * Отключаем автоинкремент первичного ключа.
     */
    public $incrementing = false;

    /**
     * Тип первичного ключа.
     */
    protected $keyType = 'string';

    /**
     * Атрибуты, для которых разрешено массовое заполнение.
     */
    protected $fillable = [
        'id', // Разрешаем ручную установку ID, если его присылают внешние тесты ТЗ
        'sku',
        'status',
        'price_cents',
        'supplier_id',
        'issued_product_code',
        'payment_idempotency_key',
        'idempotency_key',
    ];

    /**
     * Приведение типов.
     */
    protected $casts = [
        'id' => 'string',
        'status' => OrderStatus::class, // Строго через нативный Enum конечного автомата
    ];

    /**
     * @return HasMany<FinancialLedger, $this>
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(FinancialLedger::class, 'order_id', 'id');
    }

    /**
     * Контроль жизненного цикла и автоматическая генерация ID.
     */
    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->id)) {
                $order->id = Str::uuid()->toString();
            }
        });

        /**
         * Контроль переходов статусов (State Machine).
         */
        static::updating(function (Order $order) {
            if ($order->isDirty('status')) {
                $originalRaw = $order->getOriginal('status');
                $originalStatus = $originalRaw instanceof OrderStatus
                    ? $originalRaw
                    : OrderStatus::from($originalRaw);

                // 2. Безопасно приводим новый (целевой) статус к объекту Enum
                $targetRaw = $order->status;
                $targetStatus = $targetRaw instanceof OrderStatus
                    ? $targetRaw
                    : OrderStatus::from($targetRaw);

                if (!$originalStatus->canTransitionTo($targetStatus)) {
                    throw new BusinessException(
                        "Запрещен переход статуса заказа из '{$originalStatus->value}' в '{$targetStatus->value}'"
                    );
                }
            }
        });
    }
}
