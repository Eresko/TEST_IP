<?php

namespace App\Services\NotificationServices\Providers;

use App\Contracts\NotificationProvider;
use App\Dto\Notification\MessageDto;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;

class EmailProvider implements NotificationProvider
{
    public function send(User $user, MessageDto $dto): void
    {

        $email = $user->email;
        if (!$email) {
            Log::warning("Phone email is missing for user ID: {$user->id}");
            return;
        }
        Log::info("[MOCK SMS] Successfully simulated sending to email: {$email}");
        Log::debug("[MOCK SMS] Payload: " . json_encode([
                'phone' => $email,
                'notificationId' => $dto->getNotificationId(),
                'message' => $dto->getMessage()
            ], JSON_UNESCAPED_UNICODE));


    }
}
