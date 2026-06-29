<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\TimeEntry;
use App\Models\ServiceReport;
use App\Models\AccountDeletionRequest;
use App\Models\CustomerInvite;
use App\Models\ChatMessage;

echo "USERS (key test users):\n";
$keyEmails = ['admin@example.com','remial.busa@mcbtsi.com','customer@example.com',
    'ramenizing@gmail.com','adonis.ybanez@mcbtsi.com','randee.borinaga@mcbtsi.com',
    'superadmin@portal.local'];
foreach (User::whereIn('email',$keyEmails)->orderBy('id')->get() as $u) {
    printf("  %-3d  %-32s  %-11s  %-8s  %s | %s\n",
        $u->id, $u->email, $u->role, $u->status, $u->name, $u->account_name ?? '');
}

echo "\nRECENT CHAT-MESSAGE -> monday ticket refs (last 12):\n";
foreach (ChatMessage::orderBy('id','desc')->limit(12)->get() as $m) {
    printf("  %-3d  monday=%-12s  user=%-3d  dir=%-4s  body=%s\n",
        $m->id, $m->monday_item_id ?? 'null', $m->user_id ?? 0,
        $m->direction ?? '?', substr((string)$m->body, 0, 60));
}

echo "\nACTIVE TIME ENTRIES:\n";
foreach (TimeEntry::whereIn('state',['open','paused'])->get() as $te) {
    printf("  %-3d  user=%-3d  monday=%-12s  state=%-8s  started=%s  paused=%s  acc=%ds\n",
        $te->id, $te->user_id, $te->monday_item_id ?? 'null', $te->state, $te->started_at, $te->paused_at, $te->accumulated_seconds);
}

echo "\nSERVICE REPORTS (last 10):\n";
foreach (ServiceReport::orderBy('id','desc')->limit(10)->get() as $r) {
    $ss = $r->service_status instanceof \BackedEnum ? $r->service_status->value : (string) $r->service_status;
    $sx = $r->sync_state instanceof \BackedEnum ? $r->sync_state->value : (string) $r->sync_state;
    printf("  %-3d  ticket=%-12s  status=%-12s  sync=%-10s  monday_tsr_id=%-12s  mirrored=%s\n",
        $r->id, $r->monday_ticket_id ?? 'null', $ss, $sx,
        $r->monday_tsr_item_id ?? 'null', $r->mirrored_to_monday_at ?? 'null');
}

echo "\nDELETION REQUESTS (last 10):\n";
foreach (AccountDeletionRequest::orderBy('id','desc')->limit(10)->get() as $r) {
    printf("  %-3d  uid=%-5s  email=%-32s  status=%-10s\n",
        $r->id, $r->user_id === null ? 'NULL' : (string) $r->user_id, $r->email ?? 'null', $r->status);
}

echo "\nCUSTOMER INVITES (last 8):\n";
foreach (CustomerInvite::orderBy('id','desc')->limit(8)->get() as $i) {
    printf("  %-3d  email=%-32s  token=%s  expires=%s  used=%s\n",
        $i->id, $i->email, $i->token, $i->expires_at, $i->used_at ?? 'no');
}
