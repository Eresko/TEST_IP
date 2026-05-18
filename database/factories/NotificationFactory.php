<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Notification;
use App\Enums\ChannelType;
use App\Enums\StatusMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'message' => $this->faker->sentence(),
            'channel' => $this->faker->randomElement(ChannelType::cases()),
            'status' => $this->faker->randomElement(StatusMessage::cases()),
            'user_id' => User::inRandomOrder()->first()->id ?? User::factory(),
            'author_id' => User::inRandomOrder()->first()->id ?? User::factory(),
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
