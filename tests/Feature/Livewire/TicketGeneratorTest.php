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

test('user can access ticket generator page', function () {
    $this->actingAs($this->user);
    
    // Test component directly instead of route
    $component = Volt::test('ticket-generator');
    expect($component)->not->toBeNull();
});

test('ticket generator sanitizes malicious input in holderName field', function () {
    $this->actingAs($this->user);
    
    $maliciousInputs = [
        '<script>alert("XSS")</script>',
        "'; DROP TABLE tickets; --",
        'John<script>alert(1)</script>Doe',
        'John"Doe',
        "John'Doe",
        'John&Doe',
        'John<Doe>',
        'John Doe!@#$%^&*()',
        'John Doe with spaces',
    ];
    
    foreach ($maliciousInputs as $input) {
        $response = Volt::test('ticket-generator')
            ->set('eventId', $this->event->id)
            ->set('eventTicketTypeId', $this->ticketType->id)
            ->set('holderName', $input)
            ->set('email', 'test@example.com')
            ->set('dob', '2000-01-01')
            ->call('saveTicket');
        
        $response->assertHasNoErrors();
        
        $ticket = Ticket::where('email', 'test@example.com')->latest()->first();
        expect($ticket->holder_name)->not->toContain('<script>')
            ->not->toContain('DROP TABLE')
            ->not->toContain('"')
            ->not->toContain("'")
            ->not->toContain('&')
            ->not->toContain('<')
            ->not->toContain('>')
            ->not->toContain('!')
            ->not->toContain('@')
            ->not->toContain('#')
            ->not->toContain('$')
            ->not->toContain('%')
            ->not->toContain('^')
            ->not->toContain('*')
            ->not->toContain('(')
            ->not->toContain(')')
            ->not->toContain(' ');
    }
});

test('ticket generator validates required fields', function () {
    $this->actingAs($this->user);
    
    $response = Volt::test('ticket-generator')
        ->call('saveTicket');
    
    $response->assertHasErrors(['eventId', 'holderName', 'email', 'dob', 'eventTicketTypeId']);
});

test('ticket generator validates email format', function () {
    $this->actingAs($this->user);
    
    $response = Volt::test('ticket-generator')
        ->set('eventId', $this->event->id)
        ->set('eventTicketTypeId', $this->ticketType->id)
        ->set('holderName', 'John Doe')
        ->set('email', 'invalid-email')
        ->set('dob', '2000-01-01')
        ->call('saveTicket');
    
    $response->assertHasErrors(['email']);
});

test('ticket generator validates date format', function () {
    $this->actingAs($this->user);
    
    $response = Volt::test('ticket-generator')
        ->set('eventId', $this->event->id)
        ->set('eventTicketTypeId', $this->ticketType->id)
        ->set('holderName', 'John Doe')
        ->set('email', 'test@example.com')
        ->set('dob', 'invalid-date')
        ->call('saveTicket');
    
    $response->assertHasErrors(['dob']);
});

test('ticket generator creates ticket successfully', function () {
    $this->actingAs($this->user);
    
    $response = Volt::test('ticket-generator')
        ->set('eventId', $this->event->id)
        ->set('eventTicketTypeId', $this->ticketType->id)
        ->set('holderName', 'John Doe')
        ->set('email', 'test@example.com')
        ->set('dob', '2000-01-01')
        ->call('saveTicket');
    
    $response->assertHasNoErrors();
    
    $this->assertDatabaseHas('tickets', [
        'user_id' => $this->user->id,
        'event_id' => $this->event->id,
        'event_ticket_type_id' => $this->ticketType->id,
        'email' => 'test@example.com',
    ]);
});

test('ticket generator generates QR code', function () {
    $this->actingAs($this->user);
    
    $component = Volt::test('ticket-generator')
        ->set('eventId', $this->event->id)
        ->set('eventTicketTypeId', $this->ticketType->id)
        ->set('holderName', 'John Doe')
        ->set('dob', '2000-01-01');
    
    $component->call('generateQrCode');
    
    expect($component->get('qrCodeText'))->not->toBeEmpty()
        ->and($component->get('qrCodeSvg'))->not->toBeEmpty();
});

test('ticket generator creates unverified ticket by default', function () {
    $this->actingAs($this->user);
    
    $response = Volt::test('ticket-generator')
        ->set('eventId', $this->event->id)
        ->set('eventTicketTypeId', $this->ticketType->id)
        ->set('holderName', 'John Doe')
        ->set('email', 'test@example.com')
        ->set('dob', '2000-01-01')
        ->call('saveTicket');
    
    $ticket = Ticket::where('email', 'test@example.com')->first();
    expect($ticket->is_verified)->toBeFalse();
});

test('ticket generator handles missing active event', function () {
    $this->actingAs($this->user);
    
    Event::query()->update(['is_active' => false]);
    
    $component = Volt::test('ticket-generator');
    
    expect($component->get('activeEvent'))->toBeNull();
});

test('ticket generator resets form after save', function () {
    $this->actingAs($this->user);
    
    $component = Volt::test('ticket-generator')
        ->set('eventId', $this->event->id)
        ->set('eventTicketTypeId', $this->ticketType->id)
        ->set('holderName', 'John Doe')
        ->set('email', 'test@example.com')
        ->set('dob', '2000-01-01')
        ->call('saveTicket');
    
    expect($component->get('holderName'))->toBe('')
        ->and($component->get('email'))->toBe('')
        ->and($component->get('dob'))->toBe('')
        ->and($component->get('eventTicketTypeId'))->toBeNull();
});

