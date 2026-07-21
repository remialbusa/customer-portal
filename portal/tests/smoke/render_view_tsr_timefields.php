<?php
// Smoke check: render the TSR form and assert that the four
// service-time inputs are present (Login / Service start / Service
// end / Logout). Catches regressions where the Blade hides one of
// the timestamps even though the Livewire/Request/Model still
// support it.

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$out = view('livewire.tsp.tickets.create-service-report', [
    'ticketNumber'   => 'TEST',
    'lastError'      => null,
    'serviceStatus'  => 'in_progress',
    'totalMinutes'   => 0,
    'localId'        => 'test-id',
    'tspName'        => 'Test TSP',
    'tspEmail'       => 'tsp@test.local',
])->render();

$checks = [
    'Login time label'           => 'Login time',
    'Service start label'        => 'Service start',
    'Service end label'          => 'Service end',
    'Logout time label'          => 'Logout time',
    'wire:model logInDate'       => 'wire:model.live="logInDate"',
    'wire:model logOutDate'      => 'wire:model.live="logOutDate"',
    'wire:model serviceStart'    => 'wire:model.live="serviceStartDateTime"',
    'wire:model serviceEnd'      => 'wire:model.live="serviceEndDateTime"',
    'In-progress is default'     => "(\$wire.get('serviceStatus') || 'in_progress') === 'in_progress'",
];

$failed = 0;
foreach ($checks as $name => $needle) {
    $hit = str_contains($out, $needle);
    printf("%-30s %s\n", $name, $hit ? 'OK' : 'MISSING');
    if (! $hit) $failed++;
}

if ($failed > 0) {
    echo "FAIL: {$failed} check(s) missing\n";
    exit(1);
}
echo "OK: all " . count($checks) . " checks present (" . strlen($out) . " bytes rendered)\n";
