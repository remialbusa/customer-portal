<?php

namespace App\Console\Commands;

use App\Models\CustomerInvite;
use App\Models\User;
use App\Services\MondayCustomerDirectory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * Generate a single-use registration token for a customer who
 * appears on the monday.com Customer Details board.
 *
 * Usage:
 *   php artisan portal:invite-customer jane@stlukes.com
 *   php artisan portal:invite-customer jane@stlukes.com --ttl=30
 *   php artisan portal:invite-customer jane@stlukes.com --no-monday   # offline / dev
 *
 * Returns the URL the customer should visit. The admin then sends
 * that URL out (email, WhatsApp, etc.). When the customer opens it,
 * the registration form pre-fills their account / branch / region /
 * address from the snapshot stored on the invite — so they can't
 * claim to be from a different hospital, and they can only register
 * if monday.com still recognises their email.
 */
class InviteCustomerCommand extends Command
{
    protected $signature = 'portal:invite-customer
        {email : Customer email — must exist on the monday.com Customer Details board}
        {--ttl= : Token lifetime in days (default: 14)}
        {--no-monday : Skip the monday.com lookup; build the invite from a stub record (dev/test only)}
        {--invalidate-existing : Mark any prior unused invite for this email as used}';

    protected $description = 'Issue a one-time customer registration link from the monday.com Customer Details board.';

    public function handle(MondayCustomerDirectory $directory): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $ttl   = (int) ($this->option('ttl') ?? CustomerInvite::DEFAULT_TTL_DAYS);
        if ($ttl <= 0) {
            $this->error('--ttl must be a positive number of days.');
            return self::FAILURE;
        }

        // 1. Basic email sanity
        $v = Validator::make(['email' => $email], [
            'email' => ['required', 'email:rfc'],
        ]);
        if ($v->fails()) {
            $this->error('Invalid email: ' . $v->errors()->first('email'));
            return self::FAILURE;
        }

        // 2. Look up the customer on monday.com (or stub for dev)
        if ($this->option('no-monday')) {
            $this->warn('--no-monday: building a stub invite. Do NOT use this in production.');
            $customer = $this->stubRecord($email);
        } else {
            try {
                $customer = $directory->findByEmail($email);
            } catch (\Throwable $e) {
                $this->error('monday.com lookup failed: ' . $e->getMessage());
                return self::FAILURE;
            }
            if (! $customer) {
                $this->error("No customer found on monday.com for {$email}.");
                $this->line('Add a row on the Customer Details board first, or use --no-monday for testing.');
                return self::FAILURE;
            }
        }

        $this->line(sprintf(
            'Customer found: <info>%s</info> — %s · %s · %s',
            $customer['name']     ?? '(no name)',
            $customer['account_name'] ?? '(no account)',
            $customer['region']   ?? '(no region)',
            $customer['branch']   ?? '(no branch)',
        ));

        // 3. Optionally invalidate prior unused invites
        if ($this->option('invalidate-existing')) {
            $n = CustomerInvite::query()
                ->where('email', $email)
                ->whereNull('used_at')
                ->update(['used_at' => now()]);
            if ($n > 0) {
                $this->line("Invalidated {$n} prior unused invite(s) for {$email}.");
            }
        }

        // 4. Create the invite
        $invite = CustomerInvite::create([
            'token'             => CustomerInvite::generateToken(),
            'email'             => $email,
            'account_name'      => $customer['account_name'] ?? '',
            'branch'            => $customer['branch']       ?? '',
            'region'            => $customer['region']       ?? null,
            'address'           => $customer['address']      ?? null,
            'monday_customer_id'=> $customer['id']            ?? null,
            'expires_at'        => now()->addDays($ttl),
            'invited_by_user_id'=> null,
        ]);

        $url = url(route('register.withInvite', ['token' => $invite->token], false));

        $this->info('Invite created.');
        $this->line('  Token : ' . $invite->token);
        $this->line('  TTL   : ' . $ttl . ' days (expires ' . $invite->expires_at->toDayDateTimeString() . ')');
        $this->line('  URL   : ' . $url);

        return self::SUCCESS;
    }

    /**
     * Build a synthetic customer record for `--no-monday` testing.
     * Mirrors the shape produced by MondayCustomerDirectory.
     */
    protected function stubRecord(string $email): array
    {
        return [
            'id'           => 'STUB-' . substr(md5($email), 0, 8),
            'name'         => '',
            'group'        => 'NCR',
            'region'       => 'NCR',
            'branch'       => 'NCR - NATIONAL CAPITAL REGION',
            'account_name' => 'STUB Hospital (--no-monday)',
            'email'        => $email,
            'address'      => 'STUB Address, Metro Manila',
            'user_status'  => 'active',
        ];
    }
}
