<?php

namespace App\Contracts;

use App\Dto\Notification\MessageDto;
use App\Models\User;

interface NotificationProvider
{
    public function send(User $user, MessageDto $dto): void;
}