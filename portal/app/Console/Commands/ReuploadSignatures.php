<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Exceptions\MondayApiException;
use App\Models\ServiceReport;
use App\Services\MondayClient;
use Illuminate\Console\Command;
use Throwable;

/**
 * Re-upload the signature files for a TSR that's already on Monday.
 * Useful when the original sync succeeded but the signature
 * upload(s) hit a transient Monday outage.
 *
 *   php artisan monday:reupload-signatures 16
 *   php artisan monday:reupload-signatures --ticket=2750538828
 *   php artisan monday:reupload-signatures --all-synced-missing
 */
class ReuploadSignatures extends Command
{
    protected $signature = 'monday:reupload-signatures
                            {localId? : TSR local id (UUID) — primary key search}
                            {--tsr-id= : Numeric service_reports.id}
                            {--ticket= : Re-upload for all TSRs against this Monday ticket id}
                            {--all : Re-upload for every synced TSR (use carefully)}';

    protected $description = 'Re-upload TSR signature PNGs to Monday (use after a transient file-upload outage)';

    public function handle(MondayClient $monday): int
    {
        $query = ServiceReport::query()
            ->whereNotNull('monday_tsr_item_id')
            ->where('monday_tsr_item_id', '!=', '');

        if ($id = $this->argument('localId')) {
            $query->where('local_id', $id);
        } elseif ($id = $this->option('tsr-id')) {
            $query->where('id', (int) $id);
        } elseif ($ticket = $this->option('ticket')) {
            $query->where('monday_ticket_id', (int) $ticket);
        } elseif ($this->option('all')) {
            // already filtered above
        } else {
            $this->error('Specify a localId, --tsr-id, --ticket, or --all');
            return self::FAILURE;
        }

        $rows = $query->orderByDesc('id')->get();
        if ($rows->isEmpty()) {
            $this->warn('No TSRs matched.');
            return self::SUCCESS;
        }

        $cols = config('services.monday.service_report_columns');
        $okTotal = 0;
        $failTotal = 0;

        foreach ($rows as $r) {
            $this->line("TSR id={$r->id} local_id={$r->local_id} monday_item={$r->monday_tsr_item_id}");
            foreach ([
                'tsp'       => 'tsp_signature_path',
                'customer'  => 'customer_signature_path',
                'biomed'    => 'biomed_signature_path',
            ] as $role => $col) {
                $path = $r->$col;
                if (! $path) { continue; }
                $colId = $cols["{$role}_signature"] ?? $cols["{$role}_incharge_sig"] ?? null;
                if (! $colId) { $this->warn("  $role: no column id in config, skip"); continue; }
                try {
                    $assetId = $monday->attachFileWithFallback(
                        itemId:   (int) $r->monday_tsr_item_id,
                        columnId: $colId,
                        path:     $path,
                        localId:  $r->local_id,
                        role:     $role,
                    );
                    $okTotal++;
                    $this->info("  $role: OK asset=$assetId");
                } catch (MondayApiException $e) {
                    $failTotal++;
                    $this->error("  $role: FAIL " . $e->getMessage());
                } catch (Throwable $e) {
                    $failTotal++;
                    $this->error("  $role: FAIL " . $e->getMessage());
                }
            }
        }

        $this->newLine();
        $this->info("Done. ok=$okTotal fail=$failTotal");
        return $failTotal === 0 ? self::SUCCESS : self::FAILURE;
    }
}
