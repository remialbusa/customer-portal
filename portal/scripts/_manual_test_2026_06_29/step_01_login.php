<?php
/**
 * Forges an authenticated Breeze session cookie for a given email and writes
 * the cookie value to a curl-compatible Netscape cookie jar line.
 *
 * Usage: php step_01_login.php <email> <jar_path> [password]
 */
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

[$email, $jar, $password] = [$argv[1] ?? null, $argv[2] ?? null, $argv[3] ?? 'Password!123'];
if (!$email || !$jar) {
    fwrite(STDERR, "Usage: php step_01_login.php <email> <jar> [password]\n");
    exit(2);
}

$user = \App\Models\User::where('email', $email)->first();
if (!$user) {
    fwrite(STDERR, "No such user: $email\n");
    exit(1);
}

$rawKey = base64_decode(substr(config('app.key'), 7));
$cookieName = 'customerportal-session';
$prefix     = \Illuminate\Cookie\CookieValuePrefix::create($cookieName, $rawKey);

$sessionId = bin2hex(random_bytes(20));
$token     = bin2hex(random_bytes(20));

\Illuminate\Support\Facades\DB::table('sessions')->where('id', $sessionId)->delete();
\Illuminate\Support\Facades\DB::table('sessions')->insert([
    'id'            => $sessionId,
    'user_id'       => $user->id,
    'ip_address'    => '127.0.0.1',
    'user_agent'    => 'manual-test',
    'payload'       => base64_encode(json_encode([
        '_token' => $token,
        'login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d' => $user->id,
        'password_hash_' . $user->id => $user->getAuthPassword(),
        '_previous' => ['url' => 'http://127.0.0.1:8765/'],
        '_flash'    => ['old' => [], 'new' => []],
    ])),
    'last_activity' => time(),
]);

$cookieValue = \Illuminate\Support\Facades\Crypt::encryptString($prefix . $sessionId, false);

$line = sprintf(
    "127.0.0.1\tFALSE\t/\tFALSE\t%d\t%s\t%s\n",
    time() + 3600,
    $cookieName,
    $cookieValue
);

// Append to jar (curl Netscape format)
file_put_contents($jar, $line, FILE_APPEND);
echo "OK user={$user->email} role={$user->role} sessionId={$sessionId}\n";
