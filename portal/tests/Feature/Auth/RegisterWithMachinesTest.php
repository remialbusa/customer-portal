<?php

namespace Tests\Feature\Auth;

use App\Models\CustomerInvite;
use App\Models\Machine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * Tests the equipment section on /register/{token}.
 *
 * Customer must:
 *   - Supply brand + model for the primary machine (required).
 *   - Optionally supply a second machine.
 *   - See two Machine rows persisted on the new user, with
 *     is_primary correctly set.
 */
class RegisterWithMachinesTest extends TestCase
{
    use RefreshDatabase;

    public function test_primary_machine_is_required_and_secondary_optional(): void
    {
        Event::fake();
        Mail::fake();

        $invite = CustomerInvite::create([
            'email'             => 'equipmenttest@example.com',
            'token'             => 'test-token-equipment-' . uniqid(),
            'account_name'      => 'Test Hospital',
            'branch'            => 'Test Branch',
            'region'            => 'NCR',
            'address'           => 'Test address',
            'monday_customer_id'=> '9999999999',
            'expires_at'        => now()->addDays(7),
        ]);

        // Happy path: primary + secondary both filled
        Volt::test('pages.auth.register', ['token' => $invite->token])
            ->set('name', 'Equipment Tester')
            ->set('password', 'Password!123')
            ->set('password_confirmation', 'Password!123')
            ->set('primaryBrand', 'Mindray')
            ->set('primaryModel', 'BC-6800')
            ->set('primarySerial', 'SN-001')
            ->set('primaryNickname', 'Hematology main')
            ->set('secondaryBrand', 'Philips')
            ->set('secondaryModel', 'IntelliVue MX450')
            ->set('secondarySerial', 'SN-002')
            ->set('secondaryNickname', 'Bedside')
            ->call('register')
            ->assertHasNoErrors();

        $user = User::query()->where('email', $invite->email)->first();
        $this->assertNotNull($user, 'User should have been created.');

        $machines = Machine::query()->where('user_id', $user->id)
            ->orderByDesc('is_primary')->get();
        $this->assertCount(2, $machines, 'Should have 2 machine rows.');

        $primary = $machines->firstWhere('is_primary', true);
        $secondary = $machines->firstWhere('is_primary', false);

        $this->assertNotNull($primary, 'Primary machine missing.');
        $this->assertSame('Mindray', $primary->brand);
        $this->assertSame('BC-6800', $primary->model);
        $this->assertSame('SN-001', $primary->serial_number);
        $this->assertSame('Hematology main', $primary->nickname);

        $this->assertNotNull($secondary, 'Secondary machine missing.');
        $this->assertSame('Philips', $secondary->brand);
        $this->assertSame('IntelliVue MX450', $secondary->model);
        $this->assertSame('SN-002', $secondary->serial_number);
        $this->assertSame('Bedside', $secondary->nickname);

        // Invite is consumed
        $invite->refresh();
        $this->assertNotNull($invite->used_at);
        $this->assertSame((int) $user->id, (int) $invite->used_by_user_id);
    }

    public function test_secondary_can_be_skipped(): void
    {
        Event::fake();
        Mail::fake();

        $invite = CustomerInvite::create([
            'email'             => 'one-machine@example.com',
            'token'             => 'test-token-one-' . uniqid(),
            'account_name'      => 'Solo Hospital',
            'branch'            => 'Solo Branch',
            'region'            => 'NCR',
            'address'           => 'Solo',
            'monday_customer_id'=> '8888888888',
            'expires_at'        => now()->addDays(7),
        ]);

        Volt::test('pages.auth.register', ['token' => $invite->token])
            ->set('name', 'Solo Customer')
            ->set('password', 'Password!123')
            ->set('password_confirmation', 'Password!123')
            ->set('primaryBrand', 'Siemens')
            ->set('primaryModel', 'Atellica')
            ->call('register')
            ->assertHasNoErrors();

        $user = User::query()->where('email', $invite->email)->first();
        $this->assertNotNull($user);
        $this->assertCount(1, $user->machines, 'Only one machine expected.');
        $this->assertTrue($user->machines->first()->is_primary);
    }

    public function test_primary_brand_and_model_are_required(): void
    {
        Event::fake();
        Mail::fake();

        $invite = CustomerInvite::create([
            'email'             => 'no-brand@example.com',
            'token'             => 'test-token-nobrand-' . uniqid(),
            'account_name'      => 'No Brand Hospital',
            'branch'            => 'No Brand Branch',
            'region'            => 'NCR',
            'address'           => 'No Brand',
            'monday_customer_id'=> '7777777777',
            'expires_at'        => now()->addDays(7),
        ]);

        // Submit WITHOUT primaryBrand/primaryModel
        $test = Volt::test('pages.auth.register', ['token' => $invite->token])
            ->set('name', 'No Brand Customer')
            ->set('password', 'Password!123')
            ->set('password_confirmation', 'Password!123')
            ->call('register');

        $test->assertHasErrors(['primaryBrand', 'primaryModel']);

        // No user was created
        $this->assertNull(User::query()->where('email', $invite->email)->first());
        $this->assertCount(0, Machine::all());
    }
}
