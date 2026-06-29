<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Dev helper: reset known test passwords to a known value.
 * Re-runnable. Only touches dev users, not customers you care about.
 */

$devPassword = 'Password!123';
$emails = [
    'remial.busa@mcbtsi.com',     // FSE - primary test user
    'adonis.ybanez@mcbtsi.com',   // FSE
    'randee.borinaga@mcbtsi.com', // Manager
    'admin@example.com',          // Admin (was 'password')
    'customer@example.com',       // Customer
];

foreach ($emails as $email) {
    $u = User::where('email', $email)->first();
    if (! $u) {
        echo "  - {$email} : NOT FOUND\n";
        continue;
    }
    $u->password = Hash::make($devPassword);
    $u->save();
    echo "  ✓ {$email} (id={$u->id}, role={$u->role}) -> password reset to '{$devPassword}'\n";
}

echo "\nAll done. You can now log in with any of those emails + '{$devPassword}'.\n";
