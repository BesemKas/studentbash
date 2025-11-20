<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'qr_code_text',
        'holder_name',
        'dob',
        'ticket_type',
        'payment_code',
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
}
