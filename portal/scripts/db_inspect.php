<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "── service_reports columns ──\n";
$cols = collect(\Illuminate\Support\Facades\DB::select('PRAGMA table_info(service_reports)'))
    ->pluck('name');
foreach ($cols as $c) echo "  $c\n";

echo "\n── /tsp/tickets/* routes ──\n";
foreach (collect(app('router')->getRoutes()->getRoutes()) as $r) {
    $uri = $r->uri();
    if (str_contains($uri, 'tsp') || str_contains($uri, 'ticket')) {
        $m = implode('|', $r->methods());
        echo "  [$m] /$uri\n";
    }
}
