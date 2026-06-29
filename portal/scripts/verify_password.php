<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$u = User::where('email', 'remial.busa@mcbtsi.com')->first();
$ok = Hash::check('Password!123', $u->password);
echo "remial.busa@mcbtsi.com with 'Password!123' : " . ($ok ? 'OK ✓' : 'STILL WRONG') . "\n";
