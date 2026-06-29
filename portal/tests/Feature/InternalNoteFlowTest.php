<?php

namespace Tests\Feature;

use App\Models\InternalNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalNoteFlowTest extends TestCase
{
    use RefreshDatabase;

    protected ?User $admin = null;

    protected function setUp(): void
    {
        parent::setUp();
        // RefreshDatabase gives us an empty in-memory DB. Seed the
        // minimum user the controller tests need.
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_tsp_ticket_page_renders_internal_notes_panel(): void
    {
        $resp = $this->actingAs($this->admin)->get('/tsp/tickets/2749091149');

        $resp->assertStatus(200);
        $resp->assertSee('Internal notes');
        $resp->assertSee('internal-note-body', false);
    }

    public function test_tsp_can_post_internal_note(): void
    {
        $body = 'E2E test note at ' . now()->toIso8601String();

        $resp = $this->actingAs($this->admin)
            ->postJson('/tsp/tickets/2749091149/notes', ['body' => $body]);

        $resp->assertStatus(200);
        $resp->assertJson(['ok' => true, 'body' => $body]);

        $note = InternalNote::orderBy('id', 'desc')->first();
        $this->assertNotNull($note);
        $this->assertEquals($body, $note->body);
        $this->assertNotNull($note->mirrored_to_monday_at, 'Monday mirror did not run.');
    }

    public function test_empty_body_is_rejected(): void
    {
        $resp = $this->actingAs($this->admin)
            ->postJson('/tsp/tickets/2749091149/notes', ['body' => '']);
        $status = $resp->getStatusCode();
        $body   = $resp->getContent();
        $this->assertTrue(in_array($status, [302, 422]), "Got status: {$status} body: " . substr($body, 0, 200));
    }

    public function test_customer_cannot_post_internal_note(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $resp = $this->actingAs($customer)
            ->postJson('/tsp/tickets/2749091149/notes', ['body' => 'sneaky']);
        $this->assertContains($resp->getStatusCode(), [403, 404]);
    }
}
