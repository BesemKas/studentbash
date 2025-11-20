<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'event_id',
        'event_ticket_type_id',
        'qr_code_text',
        'holder_name',
        'email',
        'dob',
        'payment_ref',
        'is_verified',
        'is_vip',
        'used_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_vip' => 'boolean',
        'dob' => 'date',
        'used_at' => 'datetime',
    ];

    /**
     * Get the user that owns the ticket
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the event this ticket belongs to
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the ticket type for this ticket
     */
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(EventTicketType::class, 'event_ticket_type_id');
    }

    /**
     * Check if ticket has been used
     */
    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    /**
     * Mark ticket as used
     */
    public function markAsUsed(): void
    {
        if (!$this->isUsed()) {
            $this->update(['used_at' => now()]);
        }
    }

    /**
     * Get armband information based on ticket type
     */
    public function getArmbandInfo(): string
    {
        if (!$this->ticketType) {
            return 'Unknown';
        }

        return $this->ticketType->getArmbandColor();
    }
}
