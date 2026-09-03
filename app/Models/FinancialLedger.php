<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class FinancialLedger
 *
 * @package App\Models
 *
 * @property string $id Уникальный UUID или id финансовой записи
 * @property string $order_id UUID связанного заказа
 * @property string $type Тип проводки (deposit, refund)
 * @property int $amount_cents Сумма в копейках (положительная или отрицательная)
 * @property string $ledger_idempotency_key Уникальный ID транзакции для предотвращения дублей
 * @property Carbon $created_at Дата и время создания проводки
 *
 * @property-read Order $order Объект связанного заказа
 */
class FinancialLedger extends Model
{
    use HasUuids;

    /**
     * Имя таблицы в БД.
     *
     * @var string
     */
    protected $table = 'financial_ledger';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * Тип первичного ключа.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * @var bool
     */
    public $incrementing = false;

    /** @var list<string> */
    protected $fillable = [
        'order_id',
        'type',
        'amount_cents',
        'ledger_idempotency_key',
    ];

    /**
     * @var array<int, string>
     */
    protected $dates = [
        'created_at',
    ];

    /**
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
