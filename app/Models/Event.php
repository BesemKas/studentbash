<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Event extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'location',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get all event dates for this event
     */
    public function eventDates(): HasMany
    {
        return $this->hasMany(EventDate::class);
    }

    /**
     * Get all ticket types for this event
     */
    public function ticketTypes(): HasMany
    {
        return $this->hasMany(EventTicketType::class);
    }

    /**
     * Get all tickets for this event
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get the date range as a string
     */
    public function getDateRange(): string
    {
        return $this->start_date->format('M j') . ' - ' . $this->end_date->format('M j, Y');
    }

    /**
     * Check if event is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Generate EventDate records for each day in the date range
     */
    public function generateEventDates(): void
    {
        $colors = ['pink', 'purple', 'red', 'blue', 'green', 'yellow', 'orange', 'teal', 'indigo', 'violet'];
        
        $currentDate = $this->start_date->copy();
        $dayNumber = 1;
        $colorIndex = 0;

        // Delete existing event dates
        $this->eventDates()->delete();

        while ($currentDate->lte($this->end_date)) {
            $this->eventDates()->create([
                'date' => $currentDate->format('Y-m-d'),
                'day_number' => $dayNumber,
                'armband_color' => $colors[$colorIndex % count($colors)],
            ]);

            $currentDate->addDay();
            $dayNumber++;
            $colorIndex++;
        }
    }
}
