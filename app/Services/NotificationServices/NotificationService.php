<?php

declare(strict_types=1);

namespace App\Services\NotificationServices;

use App\Dto\Notification\MessageDto;
use App\Dto\Notification\NotificationCreateDto;
use App\Dto\Notification\NotificationIndexDto;
use App\Jobs\SendMessage;
use App\Models\Notification;
use App\Traits\HasSort;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Enums\Queue;
/**
 * Сервис для работы с сообщениями.
 */
class NotificationService
{
    use HasSort;

    /**
     * @param NotificationIndexDto $dto
     * @return LengthAwarePaginator
     */
    public function getByFilter(NotificationIndexDto $dto): LengthAwarePaginator
    {
        return Notification::query()
            ->with(['user', 'author'])

            ->when($dto->getStatus(), fn (Builder $q, $v) => $q->where('status', $v))

            ->when($dto->getChannel(), fn (Builder $q, $v) => $q->where('channel', $v))

            ->tap(fn (Builder $q) => $this->applySort($q, $dto->getSort()))
            ->paginate($dto->getPerPage(), ['*'], 'page', $dto->getPage());
    }

    /**
     * @param NotificationCreateDto $dto
     * @return Notification
     */
    public function create(NotificationCreateDto $dto): Notification
    {
        $notification = Notification::create([
            'user_id' => $dto->getUserId(),
            'author_id' => $dto->getAuthorId(),
            'message' => $dto->getMessage(),
            'channel' => $dto->getChannel(),
        ]);
        if ($notification->exists) {
            $messageDto = new MessageDto(
                $dto->getUserId(),
                $dto->getAuthorId(),
                $notification->id,
                $dto->getMessage(),
                $dto->getChannel()
            );
            
            SendMessage::dispatch($messageDto)
                ->onQueue($dto->getQueue())
                ->afterCommit();
        }

        return $notification;
    }

    /**
     * @param int $id
     * @return Notification|null
     */
    public function get(int $id): ?Notification
    {
        return Notification::query()->with(['user', 'author'])->findOrFail($id);
    }

    /**
     * @param int $id
     * @param $status
     * @return bool
     */
    public function changeStatus(int $id,$status) :bool
    {
        $notification = Notification::findOrFail($id);

        return $notification->update([
            'status' => $status
        ]);
    }
}
