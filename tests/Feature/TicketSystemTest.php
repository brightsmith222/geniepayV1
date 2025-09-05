<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class TicketSystemTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a regular user
        $this->user = User::factory()->create([
            'role' => 0,
            'email' => 'user@example.com',
        ]);
        
        // Create an admin user
        $this->admin = User::factory()->create([
            'role' => 1,
            'email' => 'admin@example.com',
        ]);
    }

    /** @test */
    public function user_can_create_ticket()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/tickets', [
            'subject' => 'Test Issue',
            'description' => 'This is a test ticket description'
        ]);

        $response->assertStatus(201)
                ->assertJson([
                    'status' => true,
                    'message' => 'Ticket created successfully'
                ]);

        $this->assertDatabaseHas('tickets', [
            'user_id' => $this->user->id,
            'subject' => 'Test Issue',
            'description' => 'This is a test ticket description',
            'status' => 'open'
        ]);

        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => 1,
            'user_id' => $this->user->id,
            'message' => 'This is a test ticket description'
        ]);
    }

    /** @test */
    public function user_can_view_their_tickets()
    {
        Sanctum::actingAs($this->user);

        // Create a ticket
        $ticket = Ticket::create([
            'user_id' => $this->user->id,
            'subject' => 'Test Issue',
            'description' => 'Test description',
            'status' => 'open'
        ]);

        $response = $this->getJson('/api/tickets');

        $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                    'message' => 'Tickets retrieved successfully'
                ])
                ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function user_can_add_reply_to_ticket()
    {
        Sanctum::actingAs($this->user);

        // Create a ticket
        $ticket = Ticket::create([
            'user_id' => $this->user->id,
            'subject' => 'Test Issue',
            'description' => 'Test description',
            'status' => 'open'
        ]);

        $response = $this->postJson("/api/tickets/{$ticket->id}/reply", [
            'message' => 'This is a reply'
        ]);

        $response->assertStatus(201)
                ->assertJson([
                    'status' => true,
                    'message' => 'Reply added successfully'
                ]);

        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'message' => 'This is a reply'
        ]);
    }

    /** @test */
    public function admin_can_update_ticket_status()
    {
        Sanctum::actingAs($this->admin);

        // Create a ticket
        $ticket = Ticket::create([
            'user_id' => $this->user->id,
            'subject' => 'Test Issue',
            'description' => 'Test description',
            'status' => 'open'
        ]);

        $response = $this->patchJson("/api/tickets/{$ticket->id}/status", [
            'status' => 'resolved'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                    'message' => 'Ticket status updated successfully'
                ]);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'resolved'
        ]);

        // Check if admin reply was added
        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => $ticket->id,
            'user_id' => null,
            'message' => 'Ticket has been resolved. Please let us know if you need further assistance.'
        ]);
    }

    /** @test */
    public function non_admin_cannot_update_ticket_status()
    {
        Sanctum::actingAs($this->user);

        // Create a ticket
        $ticket = Ticket::create([
            'user_id' => $this->user->id,
            'subject' => 'Test Issue',
            'description' => 'Test description',
            'status' => 'open'
        ]);

        $response = $this->patchJson("/api/tickets/{$ticket->id}/status", [
            'status' => 'resolved'
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_all_tickets()
    {
        Sanctum::actingAs($this->admin);

        // Create tickets for different users
        Ticket::create([
            'user_id' => $this->user->id,
            'subject' => 'User Issue',
            'description' => 'User description',
            'status' => 'open'
        ]);

        $response = $this->get('/tickets');

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_get_statistics()
    {
        Sanctum::actingAs($this->admin);

        // Create some tickets
        Ticket::create([
            'user_id' => $this->user->id,
            'subject' => 'Issue 1',
            'description' => 'Description 1',
            'status' => 'open'
        ]);

        Ticket::create([
            'user_id' => $this->user->id,
            'subject' => 'Issue 2',
            'description' => 'Description 2',
            'status' => 'resolved'
        ]);

        $response = $this->get('/tickets/statistics');

        $response->assertStatus(200);
    }
}
