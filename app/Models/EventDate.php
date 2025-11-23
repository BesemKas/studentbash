<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventDate extends Model
{
    use HasFactory;
    protected $fillable = [
        'event_id',
        'date',
        'day_number',
        'armband_color',
    ];

    protected $casts = [
        'date' => 'date',
        'day_number' => 'integer',
    ];

    /**
     * Get the event that owns this date
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get all tickets for this event date
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
