<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

foreach (['chat_messages', 'time_entries', 'internal_notes'] as $t) {
    echo PHP_EOL . "== {$t} ==" . PHP_EOL;
    $rows = DB::table($t)->limit(8)->get();
    foreach ($rows as $r) {
        echo json_encode((array) $r, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }
    echo 'total: ' . DB::table($t)->count() . PHP_EOL;
}
