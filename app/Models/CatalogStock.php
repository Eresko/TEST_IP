<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
/**
 * Class CatalogStock
 *
 * @package App\Models
 *
 * @property string $sku Уникальный артикул цифрового товара
 * @property int $available_count Текущее количество доступных для выдачи ключей
 * @property bool $is_active Флаг видимости товара на витрине каталога
 * @property Carbon|null $created_at Дата создания записи
 * @property Carbon|null $updated_at Дата последнего обновления остатков
 *
 * @method static Builder|CatalogStock activeWithStock() Скоуп для получения доступных товаров
 */
class CatalogStock extends Model
{
    /**
     * Имя таблицы в БД.
     *
     * @var string
     */
    protected $table = 'catalog_stocks';

    /**
     * Имя поля первичного ключа.
     *
     * @var string
     */
    protected $primaryKey = 'sku';

    /**
     * Тип первичного ключа (строка вместо дефолтного int).
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     *
     * @var bool
     */
    public $incrementing = false;

    /** @var list<string> */
    protected $fillable = [
        'sku',
        'available_count',
        'is_active',
    ];

    /**
     * Приведение типов данных.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'available_count' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     *
     * @param Builder<CatalogStock> $query
     * @return Builder<CatalogStock>
     */
    public function scopeActiveWithStock($query)
    {
        return $query->where('is_active', true)
            ->where('amount_cents', '>', 0);
    }
}
