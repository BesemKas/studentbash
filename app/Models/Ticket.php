<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

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
        'is_minor',
        'used_at',
        'send_email_to_holder',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_vip' => 'boolean',
        'is_minor' => 'boolean',
        'dob' => 'date',
        'used_at' => 'datetime',
        'send_email_to_holder' => 'boolean',
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

    /**
     * Calculate age from date of birth
     */
    public function age(): ?int
    {
        if (!$this->dob) {
            return null;
        }

        return $this->dob->age;
    }

    /**
     * Check if ticket holder is an adult (18 or older)
     */
    public function isAdult(): bool
    {
        $age = $this->age();
        return $age !== null && $age >= 18;
    }

    /**
     * Check if ticket holder is a minor (under 18)
     */
    public function isMinor(): bool
    {
        return !$this->isAdult();
    }

    /**
     * Boot method to automatically set is_minor when dob is set
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($ticket) {
            if ($ticket->dob) {
                $ticket->is_minor = $ticket->isMinor();
            }
        });
    }
}
