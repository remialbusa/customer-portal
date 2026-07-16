<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\MondayClient;
use Illuminate\Console\Command;
use Throwable;

/**
 * One-time setup command: create a "Submitted By" People column on
 * the EXTERNAL - TSR board (5029041107) and print the new column id
 * so it can be added to config/services.php.
 *
 * Why this exists:
 *   Monday's built-in `creation_log` column shows whoever the API
 *   token belongs to, regardless of which portal user actually
 *   triggered the action. That's why every TSR looked like it was
 *   filed by the API-token owner (Remial or the workspace admin),
 *   even when it was filed by a different FSE.
 *
 *   `creation_log` is read-only — Monday's docs list it as one of
 *   the "Read-only columns" and the API rejects any attempt to set
 *   it. The fix is a separate People column that the portal
 *   controls, which it fills with the logged-in user's monday_id.
 *
 * Usage:
 *   php artisan monday:add-tsr-submitted-by
 *   php artisan monday:add-tsr-submitted-by --title="TSR Author"
 *
 * The command is idempotent: if a People column with the same
 * title already exists, the existing id is returned and the user
 * is told how to wire it up. If a column with a different id
 * (e.g. one created manually) is the intended target, the user
 * can pass --existing-id=<id> to wire the config without touching
 * the board.
 *
 * After running, the printed column id must be pasted into
 *   config/services.php → service_report_columns.submitted_by
 * so the portal's TSR pipeline can find it.
 */
class AddTsrSubmittedByColumn extends Command
{
    protected $signature = 'monday:add-tsr-submitted-by
        {--tsr-board= : Override TSR board id (default: config)}
        {--title=Submitted By : Title of the People column to create (matched on existing columns)}
        {--existing-id= : Wire this column id into config without creating a new column on Monday}';

    protected $description = 'Create a People column on the TSR board so the portal can record the real submitting TSP (Monday\'s built-in creation_log is read-only)';

    public function handle(MondayClient $monday): int
    {
        $tsrBoard = (int) ($this->option('tsr-board') ?: config('services.monday.service_report_board_id'));
        if ($tsrBoard === 0) {
            $this->error('TSR board id is not configured. Set MONDAY_SERVICE_REPORT_BOARD_ID in .env.');
            return self::FAILURE;
        }

        $existingId = (string) ($this->option('existing-id') ?? '');
        if ($existingId !== '') {
            $this->info("Wiring up submitted_by = {$existingId} on TSR board {$tsrBoard} (no board change).");
            $this->newLine();
            $this->line('Add the following to config/services.php → service_report_columns:');
            $this->line("    'submitted_by' => '{$existingId}',");
            return self::SUCCESS;
        }

        $title = trim((string) $this->option('title'));
        if ($title === '') {
            $this->error('--title cannot be empty.');
            return self::FAILURE;
        }

        $this->info("Inspecting TSR board {$tsrBoard}…");

        $existing = $this->findExistingPeopleColumn($monday, $tsrBoard, $title);
        if ($existing !== null) {
            $this->info("Found existing People column '{$existing['title']}' with id {$existing['id']}.");
            $this->line('Add the following to config/services.php → service_report_columns:');
            $this->line("    'submitted_by' => '{$existing['id']}',");
            return self::SUCCESS;
        }

        $this->info("No People column titled '{$title}' found — creating it now…");

        $gql = <<<'GQL'
        mutation ($boardId: ID!, $title: String!) {
            create_column(
                board_id: $boardId,
                title: $title,
                column_type: people
            ) {
                id
                title
                type
            }
        }
        GQL;

        try {
            $resp = $monday->query($gql, [
                'boardId' => (string) $tsrBoard,
                'title'   => $title,
            ]);
        } catch (Throwable $e) {
            $this->error('create_column failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $col = $resp['create_column'] ?? null;
        if (! $col || empty($col['id'])) {
            $this->error('Monday returned no column id. Response: ' . json_encode($resp));
            return self::FAILURE;
        }

        $this->info("Created column '{$col['title']}' (id={$col['id']}, type={$col['type']}).");
        $this->newLine();
        $this->line('Add the following to config/services.php → service_report_columns:');
        $this->line("    'submitted_by' => '{$col['id']}',");
        $this->newLine();
        $this->line('(Tip: hide the built-in "Creation log" column on the TSR board in the Monday UI to avoid the misleading default-TSP display.)');

        return self::SUCCESS;
    }

    /**
     * Return the first People column whose title matches, or null if
     * none exists. Matches case-insensitively and trims whitespace.
     *
     * @return array{id:string,title:string,type:string}|null
     */
    protected function findExistingPeopleColumn(MondayClient $monday, int $boardId, string $title): ?array
    {
        $gql = <<<'GQL'
        query ($boardId: [ID!]) {
            boards (ids: $boardId) {
                columns { id title type }
            }
        }
        GQL;
        $resp = $monday->query($gql, ['boardId' => [(string) $boardId]]);
        $cols = $resp['boards'][0]['columns'] ?? [];

        $needle = strtolower(trim($title));
        foreach ($cols as $c) {
            if (strtolower(trim((string) $c['title'])) === $needle
                && stripos((string) $c['type'], 'people') !== false) {
                return ['id' => (string) $c['id'], 'title' => (string) $c['title'], 'type' => (string) $c['type']];
            }
        }
        return null;
    }
}
