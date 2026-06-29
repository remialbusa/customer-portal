<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\MondayClient;
use Illuminate\Console\Command;

/**
 * Diagnostic command: list every column on the TSR board (5029041107)
 * and the Tickets board (5028514175), and report whether the
 * `Service Number` relation column is wired up to the Tickets
 * board. Useful for one-time setup / debugging "itemsNotInConnectedBoards"
 * errors during TSR sync.
 *
 *   php artisan monday:diagnose-boards
 */
class DiagnoseMondayBoards extends Command
{
    protected $signature = 'monday:diagnose-boards
                            {--tsr-board= : Override TSR board id (default: config)}
                            {--tickets-board= : Override Tickets board id (default: config)}';

    protected $description = 'Inspect Monday board/column wiring for the TSR Service Number relation';

    public function handle(MondayClient $monday): int
    {
        $tsrBoard    = (int) ($this->option('tsr-board')    ?: config('services.monday.service_report_board_id'));
        $ticketsBoard = (int) ($this->option('tickets-board') ?: config('services.monday.tickets_board_id'));

        $this->info("Inspecting TSR board {$tsrBoard}…");

        $report = $monday->ensureServiceNumberLinksToTicketsBoard();
        if (! ($report['ok'] ?? false)) {
            $this->error('Inspection failed:');
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->newLine();
            $this->warn('Possible causes:');
            $this->line('  • The Service Number column ID in config/services.php is stale');
            $this->line('  • The column was renamed, retyped, or deleted in Monday');
            $this->line('  • The API token lacks read access to that board');
            $this->newLine();
            $this->info('To fix: open the TSR board in Monday, find the Service Number column,');
            $this->info('copy its column id (visible in the column URL or via the API), and update');
            $this->info("config/services.php → service_report_columns.service_number.");
            return self::FAILURE;
        }

        $this->table(
            ['key', 'value'],
            [
                ['TSR board',         $report['tsr_board_id']],
                ['Tickets board',     $report['tickets_board_id']],
                ['Column id',         $report['column_id']],
                ['Column title',      $report['column_title']],
                ['Connected boards',  implode(', ', array_map('intval', $report['connected_board_ids'] ?? []))],
                ['Tickets linked?',   $report['is_connected'] ? 'YES' : 'NO'],
                ['Next step',         $report['next_step']],
            ]
        );

        if (! $report['is_connected']) {
            $this->newLine();
            $this->warn('Action required:');
            $this->line("  1. Open TSR board {$tsrBoard} in Monday as a workspace admin");
            $this->line("  2. Click the 'Service Number' column header → Settings");
            $this->line("  3. In 'Connected boards', add board {$ticketsBoard} (Tickets)");
            $this->line("  4. Re-run: php artisan monday:diagnose-boards");
            return self::FAILURE;
        }

        $this->info('All good — Service Number is wired to the Tickets board.');
        return self::SUCCESS;
    }
}
