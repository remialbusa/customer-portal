<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$u = User::where('email', 'remial.busa@mcbtsi.com')->first();
if (! $u) {
    echo "USER NOT FOUND\n";
    exit(1);
}

echo "id={$u->id}\n";
echo "role={$u->role}\n";
echo "email={$u->email}\n";
echo "name={$u->name}\n";
echo "has_hash=" . ($u->password ? 'yes' : 'no') . "\n";
echo "hash_prefix=" . substr($u->password ?? '', 0, 7) . "\n";
echo "monday_id=" . ($u->monday_id ?? '(null)') . "\n";

// Try common dev passwords
foreach (['password', 'Password1', 'Password123', 'remial', 'remial123'] as $candidate) {
    $ok = Hash::check($candidate, $u->password ?? '');
    echo "  test '{$candidate}': " . ($ok ? 'OK' : 'no') . "\n";
}
