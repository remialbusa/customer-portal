<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\MondayCustomerDirectory;
use Illuminate\Console\Command;

/**
 * Diagnostic command: confirm MONDAY_CUSTOMERS_BOARD_ID points at a
 * board that actually contains the customers we care about. Prints
 * the configured board id, total row count, and the first few
 * (email / account) pairs so an admin can sanity-check the wiring
 * in one command — no need to open monday.com in a browser tab.
 *
 *   php artisan portal:check-customers
 *   php artisan portal:check-customers --email=ramenizing@gmail.com
 */
class DiagnoseCustomersBoard extends Command
{
    protected $signature = 'portal:check-customers
                            {--email= : Optional email to look up specifically}';

    protected $description = 'Verify MONDAY_CUSTOMERS_BOARD_ID wiring and list the customers on it';

    public function handle(MondayCustomerDirectory $directory): int
    {
        try {
            $boardId = $directory->boardId();
        } catch (\Throwable $e) {
            $this->error('Configuration error: ' . $e->getMessage());
            $this->line('Set MONDAY_CUSTOMERS_BOARD_ID in .env to the numeric id of the monday.com "Customer Details" board.');
            return self::FAILURE;
        }

        $this->info("Configured Customer Details board id: {$boardId}");

        // Force fresh fetch — no cache.
        $directory->flushCache();
        try {
            $rows = $directory->all(0);
        } catch (\Throwable $e) {
            $this->error('monday.com lookup failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('Total rows on board: ' . count($rows));
        $this->newLine();

        $target = $this->option('email');
        if ($target) {
            $hit = $directory->findByEmail($target);
            if ($hit) {
                $this->info("✓ Found \"{$target}\":");
                $this->line("    account_name : " . ($hit['account_name'] ?? '?'));
                $this->line("    branch       : " . ($hit['branch'] ?? '?'));
                $this->line("    region       : " . ($hit['region'] ?? '?'));
                $this->line("    monday item  : " . $hit['id']);
            } else {
                $this->error("✗ \"{$target}\" was NOT found on board {$boardId}.");
                $this->line('  Either the email is misspelled, or the customer is on a different board.');
                $this->line('  Check monday.com → Customer Details board → search by email.');
            }
            $this->newLine();
        }

        $this->info('First 5 rows on this board:');
        $this->table(
            ['monday_id', 'email', 'account_name', 'branch', 'region'],
            array_map(fn ($r) => [
                $r['id'],
                $r['email'] ?? '',
                $r['account_name'] ?? '',
                $r['branch'] ?? '',
                $r['region'] ?? '',
            ], array_slice($rows, 0, 5))
        );

        return self::SUCCESS;
    }
}
