<?php
/**
 * Forges a Breeze session and writes a Netscape cookie jar line.
 * Curl with -b -c should then send/receive that cookie correctly.
 *
 * Usage: php step_03_via_curl_login.php <email> <jar>
 */
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

[$email, $jar] = [$argv[1] ?? null, $argv[2] ?? null];
if (!$email || !$jar) { fwrite(STDERR, "Usage: php step_03_via_curl_login.php <email> <jar>\n"); exit(2); }

$user = \App\Models\User::where('email', $email)->first();
if (!$user) { fwrite(STDERR, "No such user\n"); exit(1); }

$rawKey = base64_decode(substr(config('app.key'), 7));
$cookieName = config('session.cookie');
$prefix = \Illuminate\Cookie\CookieValuePrefix::create($cookieName, $rawKey);
$sessionId = bin2hex(random_bytes(20));
$token = bin2hex(random_bytes(20));

// Insert session row
\Illuminate\Support\Facades\DB::table('sessions')->where('id', $sessionId)->delete();
\Illuminate\Support\Facades\DB::table('sessions')->insert([
    'id' => $sessionId,
    'user_id' => $user->id,
    'ip_address' => '127.0.0.1',
    'user_agent' => 'curl',
    'payload' => base64_encode(json_encode([
        '_token' => $token,
        'login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d' => $user->id,
        'password_hash_' . $user->id => $user->getAuthPassword(),
    ])),
    'last_activity' => time(),
]);

$cookieValue = \Illuminate\Support\Facades\Crypt::encryptString($prefix . $sessionId, false);

// Write a real Netscape jar (with header) so curl respects it
$jarContent = "# Netscape HTTP Cookie File\n"
    . "127.0.0.1\tFALSE\t/\tFALSE\t" . (time() + 3600) . "\t$cookieName\t$cookieValue\n";
file_put_contents($jar, $jarContent);
echo "Wrote $jar — sessionId=$sessionId\n";
