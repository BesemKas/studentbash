<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $holderName = fake()->name();
        $dob = fake()->date('Y-m-d', '2000-01-01');
        $event = \App\Models\Event::factory()->create();
        $event->generateEventDates();
        $ticketType = \App\Models\EventTicketType::factory()->create(['event_id' => $event->id]);

        return [
            'user_id' => \App\Models\User::factory(),
            'event_id' => $event->id,
            'event_ticket_type_id' => $ticketType->id,
            'qr_code_text' => \App\Utilities\TicketIdGenerator::generateSecureId(
                substr($ticketType->name, 0, 10),
                $dob,
                $holderName
            ),
            'holder_name' => $holderName,
            'email' => fake()->safeEmail(),
            'dob' => $dob,
            'payment_ref' => \App\Utilities\TicketIdGenerator::generatePaymentRef($holderName),
            'is_verified' => fake()->boolean(70), // 70% chance of being verified
            'is_vip' => $ticketType->is_vip,
            'used_at' => fake()->boolean(20) ? now() : null, // 20% chance of being used
        ];
    }

    /**
     * Indicate that the ticket is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }

    /**
     * Indicate that the ticket is unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => false,
        ]);
    }

    /**
     * Indicate that the ticket has been used.
     */
    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'used_at' => now(),
        ]);
    }

    /**
     * Indicate that the ticket has not been used.
     */
    public function unused(): static
    {
        return $this->state(fn (array $attributes) => [
            'used_at' => null,
        ]);
    }
}
