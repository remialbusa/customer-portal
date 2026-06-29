<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo App\Models\ServiceReport::class . ' uses route key: '
    . (new App\Models\ServiceReport())->getRouteKeyName() . PHP_EOL;
echo '--- known tickets ---' . PHP_EOL;
foreach (DB::table('service_reports')->limit(5)->get() as $r) {
    echo "id={$r->id} local_id={$r->local_id}" . PHP_EOL;
}
