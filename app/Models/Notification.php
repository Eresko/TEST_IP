<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;
use App\Enums\ChannelType;
use App\Enums\StatusMessage;
/**
 * Сообщение
 *
 * @property int            $id                     Идентификатор
 * @property string         $message                Сообщение
 * @property int            $user_id                ID пользователя
 * @property ChannelType    $channel                Канал отправки
 * @property StatusMessage  $status                 Статус сообщения
 * @property \Carbon\Carbon|null $created_at        Дата создания
 * @property \Carbon\Carbon|null $updated_at        Дата обновления
 *
 *
 * @property-read \App\Models\User $user Связанная модель пользователь
 * @property-read \App\Models\User $author Связанная модель пользователь
 *
 * @property int $count
 */
class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';



    protected $fillable = ['message', 'channel', 'status', 'user_id','author_id'];

    /**
     * Приведение типов для Eloquent
     */
    protected $casts = [
        'channel' => ChannelType::class,
        'status' => StatusMessage::class,
        'user_id'   => 'integer',
    ];



    /**
     * Отношение к пользователю
     */
    public function user():BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Отношение к автору из таблицы пользователей
     */
    public function author():BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
    

}
