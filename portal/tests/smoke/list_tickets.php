<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$rows = DB::table('service_reports')
    ->select('id', 'local_id', 'user_id', 'updated_at')
    ->orderBy('id', 'desc')
    ->limit(5)->get();
foreach ($rows as $r) {
    echo $r->id . '  local_id=' . $r->local_id . '  user=' . $r->user_id . '  ' . $r->updated_at . PHP_EOL;
}
