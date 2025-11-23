<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventTicketType extends Model
{
    use HasFactory;
    protected $fillable = [
        'event_id',
        'name',
        'description',
        'is_vip',
        'is_adult_only',
        'allowed_dates',
        'armband_color',
        'price',
    ];

    protected $casts = [
        'is_vip' => 'boolean',
        'is_adult_only' => 'boolean',
        'allowed_dates' => 'array',
        'price' => 'decimal:2',
    ];

    /**
     * Get the event that owns this ticket type
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get all tickets of this type
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Check if this ticket type is valid for a specific event date
     */
    public function isValidForDate(int $eventDateId): bool
    {
        // If allowed_dates is null or empty, it's valid for all dates (full pass)
        if (empty($this->allowed_dates)) {
            return true;
        }

        // Check if the event date ID is in the allowed dates array
        return in_array($eventDateId, $this->allowed_dates);
    }

    /**
     * Get the valid dates for this ticket type
     */
    public function getValidDates()
    {
        if (empty($this->allowed_dates)) {
            // Return all event dates if it's a full pass
            return $this->event->eventDates;
        }

        return $this->event->eventDates()->whereIn('id', $this->allowed_dates)->get();
    }

    /**
     * Check if this is a full pass (valid for all dates)
     */
    public function isFullPass(): bool
    {
        // Full pass: allowed_dates is null
        // Day pass: allowed_dates is empty array []
        return $this->allowed_dates === null;
    }

    /**
     * Get the armband color for this ticket type
     */
    public function getArmbandColor(): string
    {
        // Only return manually set color, or empty string if not set
        return $this->armband_color ?? '';
    }

    /**
     * Check if this is a VIP ticket type
     */
    public function isVip(): bool
    {
        return $this->is_vip;
    }

    /**
     * Check if this ticket type is restricted to adults only (18+)
     */
    public function isAdultOnly(): bool
    {
        return $this->is_adult_only ?? false;
    }
}
