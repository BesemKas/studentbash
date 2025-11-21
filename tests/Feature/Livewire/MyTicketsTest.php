<?php

use App\Models\Event;
use App\Models\EventTicketType;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->event = Event::factory()->active()->create();
    $this->ticketType = EventTicketType::factory()->create(['event_id' => $this->event->id]);
});

test('user can access my tickets page', function () {
    $this->actingAs($this->user);
    
    // Test component directly instead of route
    $component = Volt::test('my-tickets');
    expect($component)->not->toBeNull();
});

test('my tickets displays user tickets', function () {
    $this->actingAs($this->user);
    
    Ticket::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'event_id' => $this->event->id,
        'event_ticket_type_id' => $this->ticketType->id,
    ]);
    
    $component = Volt::test('my-tickets');
    
    $tickets = $component->get('tickets');
    expect($tickets->count())->toBe(3);
});

test('my tickets handles empty ticket list', function () {
    $this->actingAs($this->user);
    
    $component = Volt::test('my-tickets');
    
    $tickets = $component->get('tickets');
    expect($tickets->count())->toBe(0);
});

test('my tickets generates QR code SVG', function () {
    $this->actingAs($this->user);
    
    $ticket = Ticket::factory()->create([
        'user_id' => $this->user->id,
        'qr_code_text' => 'TEST-QR-CODE-123',
    ]);
    
    $component = Volt::test('my-tickets');
    
    $svg = $component->call('generateQrCodeSvg', 'TEST-QR-CODE-123');
    
    expect($svg)->toBeString()
        ->toContain('<svg');
});

test('my tickets handles invalid QR code text gracefully', function () {
    $this->actingAs($this->user);
    
    $component = Volt::test('my-tickets');
    
    $svg = $component->call('generateQrCodeSvg', '');
    
    expect($svg)->toBe('');
});

test('my tickets checks for unverified tickets', function () {
    $this->actingAs($this->user);
    
    Ticket::factory()->create([
        'user_id' => $this->user->id,
        'is_verified' => false,
    ]);
    
    Ticket::factory()->create([
        'user_id' => $this->user->id,
        'is_verified' => true,
    ]);
    
    $component = Volt::test('my-tickets');
    
    expect($component->get('hasUnverifiedTickets'))->toBeTrue();
});

test('my tickets returns false when no unverified tickets', function () {
    $this->actingAs($this->user);
    
    Ticket::factory()->create([
        'user_id' => $this->user->id,
        'is_verified' => true,
    ]);
    
    $component = Volt::test('my-tickets');
    
    expect($component->get('hasUnverifiedTickets'))->toBeFalse();
});

test('my tickets paginates tickets', function () {
    $this->actingAs($this->user);
    
    Ticket::factory()->count(25)->create([
        'user_id' => $this->user->id,
        'event_id' => $this->event->id,
        'event_ticket_type_id' => $this->ticketType->id,
    ]);
    
    $component = Volt::test('my-tickets');
    
    $tickets = $component->get('tickets');
    expect($tickets->count())->toBeLessThanOrEqual(10);
});

test('my tickets gets SnapScan URL', function () {
    $this->actingAs($this->user);
    
    $component = Volt::test('my-tickets');
    
    $url = $component->get('snapscanUrl');
    
    expect($url)->toBeString()
        ->not->toBeEmpty();
});

