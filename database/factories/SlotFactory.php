<?php

namespace Database\Factories;

use App\Models\Slot;
use Illuminate\Database\Eloquent\Factories\Factory;

class SlotFactory extends Factory
{
    protected $model = Slot::class;

    public function definition(): array
    {

        $capacity = $this->faker->numberBetween(10, 100);

        return [
            'capacity' => $capacity,
            'remaining' => $capacity,
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'updated_at' => now(),
        ];
    }
}
