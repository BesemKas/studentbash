<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventDate>
 */
class EventDateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $colors = ['pink', 'purple', 'red', 'blue', 'green', 'yellow', 'orange', 'teal', 'indigo', 'violet'];

        return [
            'event_id' => \App\Models\Event::factory(),
            'date' => fake()->dateTimeBetween('now', '+6 months'),
            'day_number' => fake()->numberBetween(1, 10),
            'armband_color' => fake()->randomElement($colors),
        ];
    }
}
