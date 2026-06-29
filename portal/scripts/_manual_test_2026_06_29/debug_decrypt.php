<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$cookieName = config('session.cookie');
echo "Session cookie name: $cookieName\n";

$rawKey = base64_decode(substr(config('app.key'), 7));
echo "Key length: " . strlen($rawKey) . " bytes\n";

// Build a session
$user = \App\Models\User::where('email', 'ramenizing@gmail.com')->first();
$sessionId = bin2hex(random_bytes(20));
$token = bin2hex(random_bytes(20));
$payload = base64_encode(json_encode([
    '_token' => $token,
    'login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d' => $user->id,
    'password_hash_' . $user->id => $user->getAuthPassword(),
]));
\Illuminate\Support\Facades\DB::table('sessions')->insert([
    'id' => $sessionId, 'user_id' => $user->id,
    'ip_address' => '127.0.0.1', 'user_agent' => 'manual-test',
    'payload' => $payload, 'last_activity' => time(),
]);

$prefix = \Illuminate\Cookie\CookieValuePrefix::create($cookieName, $rawKey);
$cookieValue = \Illuminate\Support\Facades\Crypt::encryptString($prefix . $sessionId, false);
echo "Cookie value (first 60): " . substr($cookieValue, 0, 60) . "...\n";

// Try to validate it
$decoded = \Illuminate\Support\Facades\Crypt::decryptString($cookieValue, false);
$prefix2 = substr($decoded, 0, 40);
$rest = substr($decoded, 41);
echo "Decoded prefix (first 40): $prefix2\n";
echo "Prefix matches? " . ($prefix2 === $prefix ? "YES" : "NO") . "\n";
echo "Session id: $rest (length=" . strlen($rest) . ")\n";

// And verify it's findable in DB
$row = \Illuminate\Support\Facades\DB::table('sessions')->where('id', $rest)->first();
echo "Session row found in DB? " . ($row ? "YES" : "NO") . "\n";
