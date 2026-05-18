<?php

namespace App\Services\NotificationServices\Providers;

use App\Contracts\NotificationProvider;
use App\Dto\Notification\MessageDto;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsProvider implements NotificationProvider
{
    public function send(User $user, MessageDto $dto): void
    {

        $phone = $user->phone;

        if (!$phone) {
            Log::warning("Phone number is missing for user ID: {$user->id}");
            return;
        }


        Log::info("[MOCK SMS] Successfully simulated sending to phone: {$phone}");
        Log::debug("[MOCK SMS] Payload: " . json_encode([
                'phone' => $phone,
                'notificationId' => $dto->getNotificationId(),
                'message' => $dto->getMessage()
            ], JSON_UNESCAPED_UNICODE));

    }
}
