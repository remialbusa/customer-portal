<?php
/**
 * Debug: POST a TSR payload to /tsp/tickets/2791860614/service-report
 * and print the full response + log. Mirrors what offline-tsr.js does
 * so we can see the server-side error.
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Forge a Remial session so the request authenticates as a TSP
$userId = 36;
$cookieName = config('session.cookie');
$rawKey = base64_decode(substr(config('app.key'), 7));
$sid = bin2hex(random_bytes(20));
$prefix = Illuminate\Cookie\CookieValuePrefix::create($cookieName, $rawKey);
$cookieValue = Illuminate\Support\Facades\Crypt::encryptString($prefix . $sid, false);
$now = time();
$payload = base64_encode(json_encode([
    '_token' => bin2hex(random_bytes(20)),
    'login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d' => $userId,
    'password_hash_' . $userId => DB::table('users')->where('id', $userId)->value('password'),
]));
DB::table('sessions')->insert([
    'id' => $sid,
    'user_id' => $userId,
    'ip_address' => '127.0.0.1',
    'user_agent' => 'debug-script',
    'payload' => $payload,
    'last_activity' => $now,
]);
echo "Session: $sid\n";

// Build the same payload offline-tsr.js would send
$localId = 'debug-' . bin2hex(random_bytes(8));
$ts = date('c');
$payload = [
    'local_id' => $localId,
    'ticket_number' => '2791860614',
    'client_submitted_at' => $ts,
    'service_status' => 'completed',
    'email' => 'remial.busa@mcbtsi.com',
    'problem_and_concerns' => 'Direct debug POST',
    'job_done' => '',
    'parts_replaced' => '',
    'recommendation' => '',
    'remarks' => '',
    'log_in_date' => date('Y-m-d'),
    'service_start_date_time' => date('Y-m-d\TH:i:s', strtotime('-1 hour')),
    'service_end_date_time'   => date('Y-m-d\TH:i:s'),
    'log_out_date' => date('Y-m-d'),
    'machine_system_serial_number' => 'SN-DEBUG-001',
    'software_version_no' => 'vD',
    'tsp_signature' => [
        'name' => 'Remial Busa',
        'signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=',
    ],
    'customer_in_charge' => [
        'full_name' => 'Test Customer',
        'email_address' => 'customer@example.com',
        'signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=',
    ],
    'biomed_person_in_charge' => [
        'name' => 'Test Biomed',
        'email_address' => 'biomed@example.com',
        'signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=',
    ],
    'tsp_work_with' => [],
    'total_minutes' => 60,
];

// Get a CSRF token via a real request
$ch = curl_init('http://127.0.0.1:8766/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, "$cookieName=$cookieValue");
$html = curl_exec($ch);
curl_close($ch);
if (preg_match('/name="csrf-token" content="([^"]+)"/', $html, $m)
 || preg_match('/name="csrf-token" content=\'([^\']+)\'/', $html, $m)
 || preg_match('/_token" value="([^"]+)"/', $html, $m)) {
    $token = $m[1];
} else {
    $token = bin2hex(random_bytes(20));
}
echo "CSRF: " . substr($token, 0, 8) . "...\n";

// Now POST
$ch = curl_init('http://127.0.0.1:8766/tsp/tickets/2791860614/service-report');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'X-CSRF-TOKEN: ' . $token,
    'X-XSRF-TOKEN: ' . $token,
]);
curl_setopt($ch, CURLOPT_COOKIE, "$cookieName=$cookieValue");
$resp = curl_exec($ch);
$info = curl_getinfo($ch);
$headerSize = $info['header_size'];
$body = substr($resp, $headerSize);
$code = $info['http_code'];
curl_close($ch);

echo "Status: $code\n";
echo "Body: " . substr($body, 0, 2000) . "\n";

// Look for new error in log
$log = file_get_contents(__DIR__ . '/../storage/logs/laravel.log');
$lines = explode("\n", $log);
$last = array_slice($lines, -40);
echo "\n--- Last 40 log lines ---\n";
foreach ($last as $l) echo $l . "\n";

// Cleanup
DB::table('sessions')->where('id', $sid)->delete();
