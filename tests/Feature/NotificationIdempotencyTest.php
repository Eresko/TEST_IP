<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Notification;
use App\Enums\StatusMessage;
use App\Dto\Notification\MessageDto;
use App\Jobs\SendMessage;
use App\Enums\ChannelType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Services\NotificationServices\SendService;
use Illuminate\Pipeline\Pipeline;
class NotificationIdempotencyTest extends TestCase
{
    use RefreshDatabase;


    public function test_it_does_not_send_sms_duplicate_if_already_sent(): void
    {

        Http::fake(['smsc.ru/*' => Http::response(['id' => 123], 200)]);

        $user = User::factory()->create(['phone' => '89991112233']);


        $notification = Notification::factory()->create([
            'user_id' => $user->id,
            'status' => StatusMessage::SENT->value,
        ]);

        $messageDto = new MessageDto(
            userId: $user->id,
            authorId: 1,
            notificationId: $notification->id,
            message: 'Дублирующий запрос',
            channel: ChannelType::SMS
        );


        $job = new SendMessage($messageDto);


        $result = app(Pipeline::class)
            ->send($job)
            ->through($job->middleware())
            ->then(function ($job) {
                $job->handle(app(SendService::class));
            });
        
        Http::assertNothingSent();
    }
}
