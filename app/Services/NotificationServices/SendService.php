<?php

declare(strict_types=1);

namespace App\Services\NotificationServices;

use App\Contracts\NotificationProvider;
use App\Dto\Notification\MessageDto;
use App\Enums\ChannelType;
use App\Enums\StatusMessage;
use App\Models\Notification;
use App\Services\UserServices\UserService;
use InvalidArgumentException;

/**
 * Сервис для работы с сообщениями.
 */
class SendService
{
    private array $providers = [];

    public function __construct(
        private UserService $userService,
    ) {
    }

    /**
     * @param ChannelType $type
     * @param NotificationProvider $provider
     * @return void
     */
    public function registerProvider(ChannelType $type, NotificationProvider $provider): void
    {
        $this->providers[$type->value] = $provider;
    }

    /**
     * @param MessageDto $dto
     * @return bool
     * @throws \Exception
     */
    public function send(MessageDto $dto): bool
    {
        $user = $this->userService->findById($dto->getUserId());
        $notification = Notification::find($dto->getNotificationId());
        if (!$notification || !$user) {
            $notification?->update(['status' => StatusMessage::DISCARDED->value]);

            return false;
        }
        try {
            $this->getProvider($dto->getChannel())->send($user, $dto);
            $notification->update(['status' => StatusMessage::SENT->value]);
        } catch (\Exception $e) {
            throw $e;
        }

        return true;
    }

    /**
     * @param ChannelType $channel
     * @return NotificationProvider
     */
    private function getProvider(ChannelType $channel): NotificationProvider
    {
        if (!isset($this->providers[$channel->value])) {
            throw new InvalidArgumentException("Провайдер для канала {$channel->value} не зарегистрирован.");
        }

        return $this->providers[$channel->value];
    }
}
