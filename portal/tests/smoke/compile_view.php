<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
    $out = view('livewire.tsp.tickets.create-service-report')->render();
    echo "OK: rendered " . strlen($out) . " bytes" . PHP_EOL;
} catch (\Throwable $e) {
    echo "FAIL: " . $e->getMessage() . PHP_EOL;
    echo $e->getFile() . ':' . $e->getLine() . PHP_EOL;
}
