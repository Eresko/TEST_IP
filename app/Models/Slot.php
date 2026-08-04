<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Slot extends Model
{
    use HasFactory;

    protected $fillable = [
        'capacity',
        'remaining',
    ];

    /**
     * Связь: у одного слота может быть много холдов
     */
    public function holds(): HasMany
    {
        return $this->hasMany(Hold::class);
    }
}
