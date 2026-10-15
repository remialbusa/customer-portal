<?php

namespace Tests\Feature\Admin;

use App\Models\AccountDeletionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * Admin/superadmin user management (/admin/users).
 *
 * The interesting behaviour is not the CRUD — it is the guard rails.
 * Deleting a user cascades through service reports, time entries and
 * chat history, so these tests pin down who is allowed to do what to
 * whom.
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function superadmin(): User
    {
        return User::factory()->create(['role' => 'superadmin', 'status' => 'active']);
    }

    private function customer(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'customer',
            'status' => 'active',
            'account_name' => 'St. Luke\'s',
        ], $overrides));
    }

    // ── Access control ─────────────────────────────────────────────

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    }

    public function test_customers_and_tsps_cannot_reach_the_page(): void
    {
        foreach (['customer', 'fse', 'its', 'manager'] as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => 'active']);

            $this->actingAs($user)
                ->get(route('admin.users.index'))
                ->assertForbidden();
        }
    }

    public function test_admin_and_superadmin_can_reach_the_page(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Users &amp; accounts', false);

        $this->actingAs($this->superadmin())
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    // ── Filters ────────────────────────────────────────────────────

    public function test_index_filters_by_status_and_search(): void
    {
        $admin = $this->admin();
        $suspended = $this->customer(['name' => 'Suspended Sam', 'status' => 'suspended']);
        $active = $this->customer(['name' => 'Active Ann']);

        $response = $this->actingAs($admin)
            ->get(route('admin.users.index', ['status' => 'suspended']));

        $response->assertOk();
        $response->assertSee($suspended->email);
        $response->assertDontSee($active->email);

        $searched = $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'Active Ann']));

        $searched->assertOk();
        $searched->assertSee($active->email);
        $searched->assertDontSee($suspended->email);
    }

    // ── Guard rails ────────────────────────────────────────────────

    public function test_admin_cannot_suspend_themselves(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.users.suspend', $admin))
            ->assertSessionHasErrors('user');

        $this->assertSame('active', $admin->fresh()->status);
    }

    public function test_privileged_accounts_cannot_be_touched(): void
    {
        $admin = $this->admin();
        $otherAdmin = $this->admin();
        $root = $this->superadmin();

        foreach ([$otherAdmin, $root] as $target) {
            $this->actingAs($admin)
                ->post(route('admin.users.suspend', $target))
                ->assertSessionHasErrors('user');

            $this->actingAs($admin)
                ->delete(route('admin.users.destroy', $target), ['confirm_email' => $target->email])
                ->assertSessionHasErrors('user');

            $this->actingAs($admin)
                ->get(route('admin.users.edit', $target))
                ->assertSessionHasErrors('user');

            $this->assertSame('active', $target->fresh()->status);
        }

        $this->assertDatabaseHas('users', ['id' => $root->id]);
    }

    // ── Suspend / reactivate ───────────────────────────────────────

    public function test_a_customer_can_be_suspended_and_reactivated(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();

        $this->actingAs($admin)
            ->post(route('admin.users.suspend', $customer))
            ->assertSessionHasNoErrors();

        $this->assertSame('suspended', $customer->fresh()->status);
        // Suspension must never remove the row.
        $this->assertDatabaseHas('users', ['id' => $customer->id]);

        $this->actingAs($admin)
            ->post(route('admin.users.reactivate', $customer))
            ->assertSessionHasNoErrors();

        $this->assertSame('active', $customer->fresh()->status);
    }

    // ── Edit / update ──────────────────────────────────────────────

    public function test_update_saves_operational_fields(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $customer), [
                'name' => 'Renamed Person',
                'email' => 'renamed@example.com',
                'role' => 'fse',
                'region' => 'NLuzon',
                'account_name' => 'New Hospital',
                'branch' => 'Main',
                'address' => '1 Test St',
                'team' => 'FSE',
            ])
            ->assertSessionHasNoErrors();

        $fresh = $customer->fresh();
        $this->assertSame('Renamed Person', $fresh->name);
        $this->assertSame('renamed@example.com', $fresh->email);
        $this->assertSame('fse', $fresh->role);
        $this->assertSame('NLuzon', $fresh->region);
    }

    public function test_update_cannot_promote_to_a_privileged_role(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $customer), [
                'name' => $customer->name,
                'email' => $customer->email,
                'role' => 'superadmin',
            ])
            ->assertSessionHasErrors('role');

        $this->assertSame('customer', $customer->fresh()->role);
    }

    public function test_update_requires_a_unique_email(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $other = $this->customer();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $customer), [
                'name' => $customer->name,
                'email' => $other->email,
                'role' => 'customer',
            ])
            ->assertSessionHasErrors('email');
    }

    // ── Delete ─────────────────────────────────────────────────────

    public function test_delete_is_refused_when_the_typed_email_does_not_match(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $customer), ['confirm_email' => 'wrong@example.com'])
            ->assertSessionHasErrors('confirm_email');

        $this->assertDatabaseHas('users', ['id' => $customer->id]);
    }

    public function test_delete_removes_the_user_writes_an_audit_row_and_clears_notifications(): void
    {
        $admin = $this->admin();
        $customer = $this->customer(['email' => 'gone@example.com', 'name' => 'Gone Person']);

        DB::table('notifications')->insert([
            'user_id' => $customer->id,
            'type' => 'ticket_status',
            'title' => 'Hello',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $customer), [
                'confirm_email' => 'gone@example.com',
                'reason' => 'duplicate account',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $customer->id]);
        $this->assertSame(0, DB::table('notifications')->where('user_id', $customer->id)->count());

        $audit = AccountDeletionRequest::where('email', 'gone@example.com')->first();
        $this->assertNotNull($audit);
        $this->assertSame(AccountDeletionRequest::STATUS_APPROVED, $audit->status);
        $this->assertSame('duplicate account', $audit->reason);
        $this->assertSame($admin->id, $audit->processed_by);
    }

    // ── The suspend/login interaction ──────────────────────────────

    public function test_a_suspended_account_is_not_deleted_when_it_tries_to_sign_in(): void
    {
        $customer = $this->customer(['email' => 'suspended@example.com']);
        $customer->forceFill(['status' => 'suspended'])->save();

        Volt::test('pages.auth.login')
            ->set('form.email', 'suspended@example.com')
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasErrors('form.email');

        // The whole point of suspend: the account survives.
        $this->assertDatabaseHas('users', ['id' => $customer->id, 'status' => 'suspended']);
        $this->assertGuest();
    }

    public function test_an_unclaimed_invited_stub_is_still_cleaned_up_on_sign_in(): void
    {
        $stub = User::factory()->create([
            'email' => 'stub@example.com',
            'role' => 'fse',
            'status' => 'invited',
        ]);

        Volt::test('pages.auth.login')
            ->set('form.email', 'stub@example.com')
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasErrors('form.email');

        // Pre-existing behaviour, preserved.
        $this->assertDatabaseMissing('users', ['id' => $stub->id]);
    }
}
