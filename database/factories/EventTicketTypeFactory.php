<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventTicketType>
 */
class EventTicketTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['VIP Pass', 'Full Event Pass', 'Day Pass', 'Weekend Pass', 'Early Bird'];
        $colors = ['blue', 'gold', 'silver', 'green', 'red'];

        return [
            'event_id' => \App\Models\Event::factory(),
            'name' => fake()->randomElement($types),
            'description' => fake()->sentence(),
            'is_vip' => false,
            'allowed_dates' => null, // Full pass by default
            'armband_color' => fake()->randomElement($colors),
            'price' => fake()->randomFloat(2, 50, 500),
        ];
    }

    /**
     * Indicate that the ticket type is VIP.
     */
    public function vip(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'VIP Pass',
            'is_vip' => true,
            'allowed_dates' => null,
            'armband_color' => null,
        ]);
    }

    /**
     * Indicate that the ticket type is a day pass.
     */
    public function dayPass(): static
    {
        return $this->state(function (array $attributes) {
            $eventId = $attributes['event_id'] ?? null;
            if ($eventId) {
                $event = \App\Models\Event::with('eventDates')->find($eventId);
                if ($event && $event->eventDates->count() > 0) {
                    $randomDate = $event->eventDates->random();
                    return [
                        'name' => 'Day ' . $randomDate->day_number . ' Only',
                        'is_vip' => false,
                        'allowed_dates' => [$randomDate->id],
                        'armband_color' => null,
                    ];
                }
            }
            return [
                'name' => 'Day Pass',
                'is_vip' => false,
                'allowed_dates' => [],
                'armband_color' => null,
            ];
        });
    }
}
