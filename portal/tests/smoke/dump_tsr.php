<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$r = App\Models\ServiceReport::find(16);
if (!$r) { echo json_encode(['error' => 'no row']); exit; }
echo json_encode([
    'id' => $r->id,
    'sync_state' => $r->sync_state?->value ?? (string) $r->sync_state,
    'monday_tsr_item_id' => $r->monday_tsr_item_id,
    'monday_ticket_id' => $r->monday_ticket_id,
    'tsp_signature_path' => $r->tsp_signature_path,
    'customer_signature_path' => $r->customer_signature_path,
    'biomed_signature_path' => $r->biomed_signature_path,
    'mirrored_to_monday_at' => $r->mirrored_to_monday_at?->toIso8601String(),
]);
