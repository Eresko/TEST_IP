<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Http\Requests\NotificationCreateRequest;
use App\Dto\Notification\NotificationCreateDto;
use App\Enums\ChannelType;
use App\Enums\Queue;
use App\Models\User; // Добавлено
use Illuminate\Support\Facades\Validator;

class NotificationCreateTest extends TestCase
{
    /**
     * Проверяем правильную трансформацию валидных данных в DTO.
     */
    public function test_it_transforms_request_data_to_dto_correctly(): void
    {

        $user = User::first() ?? User::factory()->create();

        $data = [
            'user_id' => $user->id,
            'author_id' => $user->id,
            'message' => 'Тестовое логистическое уведомление',
            'channel' => 'sms',
            'queue' => 'high'
        ];


        $request = new NotificationCreateRequest();
        $request->merge($data);


        $validator = Validator::make($data, $request->rules());
        $request->setValidator($validator);


        $dto = $request->toDto();


        $this->assertInstanceOf(NotificationCreateDto::class, $dto);
        $this->assertEquals($user->id, $dto->getUserId());
        $this->assertEquals($user->id, $dto->getAuthorId());
        $this->assertEquals('Тестовое логистическое уведомление', $dto->getMessage());
        $this->assertEquals(ChannelType::SMS, $dto->getChannel());
        $this->assertEquals(Queue::HIGH, $dto->getQueue());
    }

    /**
     * Проверяем, что если очередь не передана, подставляется дефолтное значение.
     */
    public function test_it_uses_default_queue_when_not_provided(): void
    {

        $user = User::first() ?? User::factory()->create();


        $data = [
            'user_id' => $user->id,
            'author_id' => $user->id,
            'channel' => 'email',
            'message' => 'Тест без очереди',
        ];

        $request = new NotificationCreateRequest();
        $request->merge($data);
        $request->setValidator(Validator::make($data, $request->rules()));

        $dto = $request->toDto();

        $this->assertEquals(Queue::DEFAULT, $dto->getQueue());
    }
}
