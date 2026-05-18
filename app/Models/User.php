<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
/**
 * Пользователь
 *
 * @property int         $id        Идентификатор
 * @property string      $name      Имя пользователя
 * @property string      $email     Email
 * @property integer     $phone     Номер телефона
 * @property \Carbon\Carbon|null  $created_at  Дата создания
 * @property \Carbon\Carbon|null  $updated_at  Дата обновления
 */

class User extends Model
{
    use HasFactory;
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'phone',

    ];

    public function notification(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

}