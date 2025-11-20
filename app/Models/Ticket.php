<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    protected $fillable = [
        'user_id',
        'qr_code_text',
        'holder_name',
        'email',
        'dob',
        'ticket_type',
        'payment_ref',
        'is_verified',
        'is_vip',
        'd4_used',
        'd5_used',
        'd6_used',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_vip' => 'boolean',
        'd4_used' => 'boolean',
        'd5_used' => 'boolean',
        'd6_used' => 'boolean',
        'dob' => 'date',
    ];

    /**
     * Get the user that owns the ticket
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
