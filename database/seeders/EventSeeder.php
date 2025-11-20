<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventTicketType;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a default active event (December 2024)
        $defaultEvent = Event::create([
            'name' => 'December 2024 Event',
            'location' => 'Cape Town Convention Centre',
            'start_date' => '2024-12-04',
            'end_date' => '2024-12-06',
            'is_active' => true,
        ]);

        // Generate event dates
        $defaultEvent->generateEventDates();

        // Get the event dates
        $eventDates = $defaultEvent->eventDates()->orderBy('date')->get();

        // Create ticket types for the default event
        if ($eventDates->count() >= 3) {
            // VIP Pass
            EventTicketType::create([
                'event_id' => $defaultEvent->id,
                'name' => 'VIP Pass',
                'description' => 'VIP access to all event dates with exclusive benefits',
                'is_vip' => true,
                'allowed_dates' => null, // All dates
                'armband_color' => null,
                'price' => 500.00,
            ]);

            // Full Event Pass
            EventTicketType::create([
                'event_id' => $defaultEvent->id,
                'name' => 'Full Event Pass',
                'description' => 'Access to all event dates',
                'is_vip' => false,
                'allowed_dates' => null, // All dates
                'armband_color' => 'blue',
                'price' => 300.00,
            ]);

            // Day 1 Only
            EventTicketType::create([
                'event_id' => $defaultEvent->id,
                'name' => 'Day 1 Only',
                'description' => 'Access for Day 1 only',
                'is_vip' => false,
                'allowed_dates' => [$eventDates[0]->id],
                'armband_color' => null,
                'price' => 100.00,
            ]);

            // Day 2 Only
            EventTicketType::create([
                'event_id' => $defaultEvent->id,
                'name' => 'Day 2 Only',
                'description' => 'Access for Day 2 only',
                'is_vip' => false,
                'allowed_dates' => [$eventDates[1]->id],
                'armband_color' => null,
                'price' => 100.00,
            ]);

            // Day 3 Only
            EventTicketType::create([
                'event_id' => $defaultEvent->id,
                'name' => 'Day 3 Only',
                'description' => 'Access for Day 3 only',
                'is_vip' => false,
                'allowed_dates' => [$eventDates[2]->id],
                'armband_color' => null,
                'price' => 100.00,
            ]);
        }

        // Create a few more sample events
        Event::factory()
            ->count(3)
            ->active()
            ->create()
            ->each(function ($event) {
                $event->generateEventDates();
                
                // Create ticket types for each event
                EventTicketType::factory()->vip()->create(['event_id' => $event->id]);
                EventTicketType::factory()->create(['event_id' => $event->id]); // Full pass
                
                // Create a couple day passes
                if ($event->eventDates->count() > 0) {
                    EventTicketType::factory()->dayPass()->create(['event_id' => $event->id]);
                    if ($event->eventDates->count() > 1) {
                        EventTicketType::factory()->dayPass()->create(['event_id' => $event->id]);
                    }
                }
            });
    }
}
