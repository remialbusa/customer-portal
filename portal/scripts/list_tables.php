<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "--- TABLES ---\n";
$tables = collect(DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"))
    ->map(fn ($r) => $r->name)
    ->all();
foreach ($tables as $name) {
    echo "  $name\n";
}

echo "\n--- TICKET-LIKE TABLES (rows + columns) ---\n";
foreach ($tables as $name) {
    if (! str_contains($name, 'ticket') && ! str_contains($name, 'service')) continue;

    echo "\n[$name]\n";
    $cols = Schema::getColumnListing($name);
    echo "  cols: " . implode(', ', $cols) . "\n";
    $count = DB::table($name)->count();
    echo "  rows: $count\n";
    if ($count > 0) {
        $sample = DB::table($name)->limit(3)->get()->map(fn($x) => (array) $x)->all();
        foreach ($sample as $i => $row) {
            echo "  sample[" . ($i + 1) . "]: " . json_encode(array_slice($row, 0, 8, true)) . "\n";
        }
    }
}
