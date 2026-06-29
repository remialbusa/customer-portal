<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\TimeEntry;
$e = TimeEntry::latest()->first();
if ($e) {
    echo 'columns: ' . implode(',', array_keys($e->getAttributes())) . PHP_EOL;
    echo 'attrs: ' . print_r($e->getAttributes(), true);
} else {
    echo 'no time entries' . PHP_EOL;
}
