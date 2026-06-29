<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$r = App\Models\ServiceReport::find(16);
if (!$r) { echo ''; exit; }
echo $r->monday_ticket_id;
