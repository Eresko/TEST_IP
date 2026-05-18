<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Dto\Notification\NotificationIndexDto;
use App\Enums\ChannelType;
use App\Enums\StatusMessage;
use Illuminate\Validation\Rules\Enum;
class NotificationIndexRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'status'  => ['nullable', new Enum(StatusMessage::class)],
            'channel'  => ['nullable', new Enum(ChannelType::class)],
            'sort'    => 'nullable|string|in:status_asc,status_desc,channel_desc,channel_asc',
            'page'    => 'nullable|numeric|min:1',
        ];
    }

    /**
     * Преобразуем входные данные в DTO
     */
    public function toDto(): NotificationIndexDto
    {
        return new NotificationIndexDto(
            status: StatusMessage::from($this->validated('status')),
            chanel: ChannelType::from($this->validated('channel')),
            sort: $this->input('sort') ?? null,
            page: $this->input('page') ?? null,
        );
    }
}