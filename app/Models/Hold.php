<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\HoldStatus;
class Hold extends Model
{
    use HasFactory;

    protected $fillable = [
        'slot_id',
        'idempotency_key',
        'status',
        'response_data',
        'expires_at',
    ];


    protected function casts(): array
    {
        return [
            'status' => HoldStatus::class,
            'response_data' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Связь: холд принадлежит конкретному слоту
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(Slot::class);
    }
}
