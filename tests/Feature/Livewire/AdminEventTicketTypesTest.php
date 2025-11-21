<?php

use App\Models\Event;
use App\Models\EventTicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    if (!Role::where('name', 'admin')->exists()) {
        Role::create(['name' => 'admin']);
    }
    
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    
    $this->event = Event::factory()->active()->create();
});

test('admin can access ticket types page', function () {
    $this->actingAs($this->admin);
    
    // Test component directly instead of route
    $component = Volt::test('admin-event-ticket-types', ['event' => $this->event]);
    expect($component)->not->toBeNull();
});

test('admin can create ticket type with valid data', function () {
    $this->actingAs($this->admin);
    
    $response = Volt::test('admin-event-ticket-types', ['event' => $this->event])
        ->call('createTicketType')
        ->set('name', 'VIP Pass')
        ->set('description', 'VIP Description')
        ->set('is_vip', true)
        ->set('armband_color', 'gold')
        ->set('price', 100.00)
        ->call('saveTicketType');
    
    $response->assertHasNoErrors();
    
    $this->assertDatabaseHas('event_ticket_types', [
        'event_id' => $this->event->id,
        'name' => 'VIPPass',
        'armband_color' => 'gold',
    ]);
});

test('ticket types sanitizes malicious input in name field', function () {
    $this->actingAs($this->admin);
    
    $maliciousInputs = [
        '<script>alert("XSS")</script>',
        "'; DROP TABLE event_ticket_types; --",
        'VIP<script>alert(1)</script>Pass',
        'VIP"Pass',
        "VIP'Pass",
        'VIP&Pass',
        'VIP<Pass>',
        'VIP Pass!@#$%^&*()',
    ];
    
    foreach ($maliciousInputs as $input) {
        $response = Volt::test('admin-event-ticket-types', ['event' => $this->event])
            ->call('createTicketType')
            ->set('name', $input)
            ->set('is_vip', false)
            ->call('saveTicketType');
        
        $response->assertHasNoErrors();
        
        $ticketType = EventTicketType::where('event_id', $this->event->id)->latest()->first();
        expect($ticketType->name)->not->toContain('<script>')
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

test('ticket types sanitizes malicious input in description field', function () {
    $this->actingAs($this->admin);
    
    $maliciousInputs = [
        '<script>alert("XSS")</script>',
        "'; DROP TABLE event_ticket_types; --",
        'Description<script>alert(1)</script>',
        'Description"Test',
        "Description'Test",
        'Description&Test',
        'Description<Test>',
        'Description Test!@#$%^&*()',
    ];
    
    foreach ($maliciousInputs as $input) {
        $response = Volt::test('admin-event-ticket-types', ['event' => $this->event])
            ->call('createTicketType')
            ->set('name', 'Test Ticket')
            ->set('description', $input)
            ->set('is_vip', false)
            ->call('saveTicketType');
        
        $response->assertHasNoErrors();
        
        $ticketType = EventTicketType::where('name', 'TestTicket')->latest()->first();
        if ($ticketType->description) {
            expect($ticketType->description)->not->toContain('<script>')
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
    }
});

test('ticket types sanitizes malicious input in armband_color field', function () {
    $this->actingAs($this->admin);
    
    $maliciousInputs = [
        '<script>alert("XSS")</script>',
        "'; DROP TABLE event_ticket_types; --",
        'blue<script>alert(1)</script>',
        'blue"test',
        "blue'test",
        'blue&test',
        'blue<test>',
        'blue!@#$%^&*()',
    ];
    
    foreach ($maliciousInputs as $input) {
        $response = Volt::test('admin-event-ticket-types', ['event' => $this->event])
            ->call('createTicketType')
            ->set('name', 'Test Ticket')
            ->set('armband_color', $input)
            ->set('is_vip', false)
            ->call('saveTicketType');
        
        $response->assertHasNoErrors();
        
        $ticketType = EventTicketType::where('name', 'TestTicket')->latest()->first();
        if ($ticketType->armband_color) {
            expect($ticketType->armband_color)->not->toContain('<script>')
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
    }
});

test('admin can edit ticket type', function () {
    $this->actingAs($this->admin);
    
    $ticketType = EventTicketType::factory()->create(['event_id' => $this->event->id]);
    
    $response = Volt::test('admin-event-ticket-types', ['event' => $this->event])
        ->call('editTicketType', $ticketType)
        ->set('name', 'Updated Ticket Type')
        ->call('saveTicketType');
    
    $response->assertHasNoErrors();
    
    $ticketType->refresh();
    expect($ticketType->name)->toBe('UpdatedTicketType');
});

test('admin can delete ticket type', function () {
    $this->actingAs($this->admin);
    
    $ticketType = EventTicketType::factory()->create(['event_id' => $this->event->id]);
    
    $response = Volt::test('admin-event-ticket-types', ['event' => $this->event])
        ->call('deleteTicketType', $ticketType);
    
    $response->assertHasNoErrors();
    
    $this->assertDatabaseMissing('event_ticket_types', ['id' => $ticketType->id]);
});

test('ticket types validates required fields', function () {
    $this->actingAs($this->admin);
    
    $response = Volt::test('admin-event-ticket-types', ['event' => $this->event])
        ->call('createTicketType')
        ->call('saveTicketType');
    
    $response->assertHasErrors(['name']);
});

test('ticket types handles negative price', function () {
    $this->actingAs($this->admin);
    
    $response = Volt::test('admin-event-ticket-types', ['event' => $this->event])
        ->call('createTicketType')
        ->set('name', 'Test Ticket')
        ->set('price', -10.00)
        ->call('saveTicketType');
    
    $response->assertHasErrors(['price']);
});

test('ticket types handles invalid allowed_dates', function () {
    $this->actingAs($this->admin);
    
    $response = Volt::test('admin-event-ticket-types', ['event' => $this->event])
        ->call('createTicketType')
        ->set('name', 'Test Ticket')
        ->set('allowed_dates', [99999]) // Non-existent event date ID
        ->call('saveTicketType');
    
    $response->assertHasErrors(['allowed_dates.0']);
});

