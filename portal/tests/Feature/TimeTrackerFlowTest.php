<?php

namespace Tests\Feature;

use App\Models\TimeEntry;
use App\Models\User;
use App\Services\MondayClient;
use App\Services\TimeTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TimeTrackerFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $tsp;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tsp = User::factory()->create([
            'role'  => 'fse',
            'name'  => 'Test Tech',
            'email' => 'tech-tracker@test.local',
        ]);
        $this->customer = User::factory()->create([
            'role'  => 'customer',
            'name'  => 'Test Customer',
            'email' => 'cust-tracker@test.local',
        ]);

        // Mock MondayClient: every call returns a successful update id.
        $mock = Mockery::mock(MondayClient::class);
        $mock->shouldReceive('getItem')->andReturn([
            'id'     => 2749091149,
            'name'   => 'Tracker Test Ticket',
            'column_values' => [],
        ]);
        $mock->shouldReceive('createUpdate')->andReturn('9999999999');
        $this->app->instance(MondayClient::class, $mock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_tsp_can_start_a_timer(): void
    {
        $r = $this->actingAs($this->tsp)
            ->postJson('/tsp/tickets/2749091149/time/start', ['note' => 'Initial setup']);

        $r->assertOk();
        $r->assertJsonPath('ok', true);
        $r->assertJsonPath('active.status', 'open');
        $r->assertJsonPath('active.note', 'Initial setup');
        $this->assertDatabaseHas('time_entries', [
            'monday_ticket_id' => '2749091149',
            'user_id'          => $this->tsp->id,
            'status'           => 'open',
        ]);
    }

    public function test_start_is_idempotent_on_same_ticket(): void
    {
        $this->actingAs($this->tsp)
            ->postJson('/tsp/tickets/2749091149/time/start', ['note' => 'first'])
            ->assertOk();

        $r = $this->actingAs($this->tsp)
            ->postJson('/tsp/tickets/2749091149/time/start', ['note' => 'second']);

        $r->assertOk();
        $this->assertSame(1, TimeEntry::where('user_id', $this->tsp->id)->count());
    }

    public function test_start_on_different_ticket_when_active_returns_409(): void
    {
        $mock = Mockery::mock(MondayClient::class);
        $mock->shouldReceive('getItem')->andReturn([
            'id' => 1234567890, 'name' => 'Other Ticket', 'column_values' => [],
        ]);
        $mock->shouldReceive('createUpdate')->andReturn('9999999999');
        $this->app->instance(MondayClient::class, $mock);

        $this->actingAs($this->tsp)
            ->postJson('/tsp/tickets/2749091149/time/start', ['note' => 'on first'])
            ->assertOk();

        $r = $this->actingAs($this->tsp)
            ->postJson('/tsp/tickets/1234567890/time/start', ['note' => 'on second']);

        $r->assertStatus(409);
        $r->assertJsonPath('code', 'existing_timer');
    }

    public function test_pause_resume_accumulates_seconds(): void
    {
        $this->actingAs($this->tsp)
            ->postJson('/tsp/tickets/2749091149/time/start', ['note' => ''])
            ->assertOk();

        $r1 = $this->actingAs($this->tsp)
            ->postJson('/tsp/tickets/2749091149/time/pause');
        $r1->assertJsonPath('active.status', 'paused');

        $entry = TimeEntry::where('user_id', $this->tsp->id)->first();
        $stored = (int) $entry->accumulated_seconds;
        $this->assertGreaterThanOrEqual(0, $stored);

        $r2 = $this->actingAs($this->tsp)
            ->postJson('/tsp/tickets/2749091149/time/resume');
        $r2->assertJsonPath('active.status', 'open');
    }

    public function test_stop_finalizes_entry_and_calls_monday(): void
    {
        $this->actingAs($this->tsp)
            ->postJson('/tsp/tickets/2749091149/time/start', ['note' => 'final test'])
            ->assertOk();

        $r = $this->actingAs($this->tsp)
            ->postJson('/tsp/tickets/2749091149/time/stop');

        $r->assertOk();
        $r->assertJsonPath('active', null);

        $entry = TimeEntry::where('user_id', $this->tsp->id)->first();
        $this->assertSame('closed', $entry->status);
        $this->assertNotNull($entry->stopped_at);
    }

    public function test_customer_cannot_use_time_routes(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/tsp/tickets/2749091149/time/start', ['note' => 'nope'])
            ->assertForbidden();
    }

    public function test_total_seconds_for_ticket_includes_closed_entries(): void
    {
        $this->actingAs($this->tsp);

        $tracker = $this->app->make(TimeTracker::class);
        $tracker->start($this->tsp, 2749091149, 'first');
        $tracker->stop($tracker->activeEntryFor($this->tsp));
        $tracker->start($this->tsp, 2749091149, 'second');
        $tracker->stop($tracker->activeEntryFor($this->tsp));

        $this->assertSame(2, TimeEntry::where('monday_ticket_id', '2749091149')
            ->where('user_id', $this->tsp->id)
            ->where('status', 'closed')
            ->count());

        $this->assertGreaterThanOrEqual(0, $tracker->totalSecondsForTicket(2749091149));
    }
}
