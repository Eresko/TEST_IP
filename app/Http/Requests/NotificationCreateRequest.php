<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Dto\Notification\NotificationIndexDto;
use App\Dto\Notification\NotificationCreateDto;
use App\Enums\ChannelType;
use App\Enums\Queue;
use Illuminate\Validation\Rules\Enum;
class NotificationCreateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'user_id' => 'nullable|integer|exists:users,id',
            'author_id' => 'nullable|integer|exists:users,id',
            'message'  => 'string|max:500',
            'channel' => ['required', new Enum(ChannelType::class)],
            'queue'     => ['nullable', new Enum(Queue::class)],
        ];
    }

    /**
     * Преобразуем входные данные в DTO
     */
    public function toDto(): NotificationCreateDto
    {
        $queueValue = $this->validated('queue');
        return new NotificationCreateDto(
            userId: $this->validated('user_id'),
            authorId: $this->validated('author_id'),
            message: $this->validated('message'),
            channel: ChannelType::from($this->validated('channel')),
            queue: $queueValue ? Queue::from($queueValue) : Queue::DEFAULT
        );
    }
}