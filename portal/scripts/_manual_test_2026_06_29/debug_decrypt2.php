<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$cookieName = config('session.cookie');
$rawKey = base64_decode(substr(config('app.key'), 7));

$prefix = \Illuminate\Cookie\CookieValuePrefix::create($cookieName, $rawKey);
echo "Expected prefix (raw): " . bin2hex($prefix) . " len=" . strlen($prefix) . "\n";
echo "Expected HMAC: " . hash_hmac('sha1', $cookieName.'v2', $rawKey) . "\n";

// Encrypt then decrypt
$sessionId = bin2hex(random_bytes(20));
$payload = $prefix . $sessionId;
$enc = \Illuminate\Support\Facades\Crypt::encryptString($payload, false);
echo "Encrypted: " . substr($enc, 0, 60) . "...\n";

$dec = \Illuminate\Support\Facades\Crypt::decryptString($enc, false);
echo "Decrypted (raw bytes): " . bin2hex($dec) . "\n";
echo "Decrypted first 41 chars: " . bin2hex(substr($dec, 0, 41)) . "\n";
echo "Decrypted first 40 chars: " . bin2hex(substr($dec, 0, 40)) . "\n";
echo "Decrypted 41st char: '" . substr($dec, 40, 1) . "' (ord=" . ord(substr($dec, 40, 1)) . ")\n";
echo "Decrypted 42nd char: '" . substr($dec, 41, 1) . "'\n";
echo "Rest (session id): $sessionId (len=" . strlen($sessionId) . ")\n";
echo "Match (40 chars)? " . (substr($dec, 0, 40) === hash_hmac('sha1', $cookieName.'v2', $rawKey) ? "YES" : "NO") . "\n";
echo "Match (41 chars including |)? " . (substr($dec, 0, 41) === $prefix ? "YES" : "NO") . "\n";
