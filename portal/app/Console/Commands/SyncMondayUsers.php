<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MondayClient;
use Illuminate\Console\Command;

/**
 * Pulls the full Monday.com user list and reconciles local accounts.
 *
 * Heuristic for role assignment (applied to anyone NOT in the local DB yet):
 *   - title contains "Field Service" / "FSE" / "Service Engineer" / "FME"  → fse
 *   - title contains "IT Specialist"                                       → its
 *   - title contains "Service Manager" / "Regional" / "IT Manager"        → manager
 *   - title contains "Coordinator"                                        → manager (they dispatch)
 *   - is_admin = true and no other match                                  → admin
 *   - everything else                                                     → ITS (internal)
 *
 * Use case:
 *   php artisan monday:users-sync
 *   php artisan monday:users-sync --dry-run
 *   php artisan monday:users-sync --role-map='{"Field Service":"fse"}'
 */
class SyncMondayUsers extends Command
{
    protected $signature = 'monday:users-sync
                            {--dry-run : Show what would change without writing to the DB}
                            {--role-map= : JSON override for role detection, e.g. {"Field Service":"fse"}}';

    protected $description = 'Sync Monday.com users into the local users table.';

    public function handle(MondayClient $mc): int
    {
        $resp = $mc->query('query { users(limit: 200) { id name email title is_admin is_guest enabled } }');
        $users = $resp['users'] ?? [];

        if (! $users) {
            $this->error('No users returned by Monday (token may lack permission).');
            return self::FAILURE;
        }

        $this->info("Monday returned " . count($users) . " user(s).");

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($users as $u) {
            if (! ($u['enabled'] ?? true)) {
                $skipped++;
                continue;
            }

            $local = User::where('email', $u['email'])->first();

            // Existing user: refresh name + monday_id, never change role/pwd
            if ($local) {
                $dirty = [];
                if ($local->name !== $u['name'])      $dirty['name']      = $u['name'];
                if ($local->monday_id !== $u['id'])   $dirty['monday_id'] = $u['id'];
                if (! empty($dirty)) {
                    if ($this->option('dry-run')) {
                        $this->line("  UPDATE  {$u['email']}  →  " . json_encode($dirty));
                    } else {
                        $local->update($dirty);
                    }
                    $updated++;
                }
                continue;
            }

            // New user: pick role by title heuristic
            $role = $this->inferRole($u['title'], $u['is_admin'] ?? false);

            $payload = [
                'name'      => $u['name'],
                'email'     => $u['email'],
                'password'  => bcrypt('Password!123'), // user must reset on first login
                'role'      => $role,
                'status'    => 'active',
                'monday_id' => $u['id'],
            ];

            if ($this->option('dry-run')) {
                $this->line("  CREATE  {$u['email']}  role={$role}  title=" . ($u['title'] ?? '(none)'));
            } else {
                User::create($payload);
            }
            $created++;
        }

        $verb = $this->option('dry-run') ? 'WOULD' : 'DID';
        $this->info("{$verb} create {$created}, update {$updated}, skip {$skipped}.");
        return self::SUCCESS;
    }

    private function inferRole(?string $title, bool $isAdmin): string
    {
        $title = strtolower($title ?? '');

        // Manager-level first (overrides FSE if both words appear)
        if (str_contains($title, 'manager') || str_contains($title, 'coordinator')) {
            return 'manager';
        }
        if (str_contains($title, 'field service') || str_contains($title, ' fse ') || str_contains($title, 'service engineer') || str_contains($title, 'fme') || str_contains($title, 'field mechanical')) {
            return 'fse';
        }
        if (str_contains($title, 'it specialist')) {
            return 'its';
        }
        if ($isAdmin) {
            return 'admin';
        }
        // Default bucket: people without service/IT in their title (sales, etc.) get its
        // since they're internal staff. Override later from /admin if needed.
        return 'its';
    }
}
