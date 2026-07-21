<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$count = DB::table('service_reports')->count();
echo "Total service_reports: $count\n";

$latest = DB::table('service_reports')
    ->orderBy('id', 'desc')
    ->limit(3)
    ->get(['id', 'local_id', 'monday_ticket_id', 'sync_state', 'user_id', 'created_at']);
foreach ($latest as $r) {
    echo "  id={$r->id} user={$r->user_id} ticket={$r->monday_ticket_id} state={$r->sync_state} local=" . substr($r->local_id ?? '', 0, 12) . " created={$r->created_at}\n";
}
