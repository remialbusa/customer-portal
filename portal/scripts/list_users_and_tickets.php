<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Ticket;
use App\Models\User;

echo "--- USERS ---\n";
printf("%-4s %-10s %-35s %s\n", 'id', 'role', 'email', 'name');
foreach (User::select('id', 'name', 'email', 'role')->orderBy('id')->get() as $u) {
    printf("%-4d %-10s %-35s %s\n", $u->id, $u->role, $u->email, $u->name);
}

echo "\n--- TICKETS (first 25) ---\n";
printf("%-4s %-12s %-6s %-6s %s\n", 'id', 'monday_id', 'cust', 'asgn', 'subject');
foreach (Ticket::select('id', 'monday_item_id', 'subject', 'status', 'assigned_user_id', 'customer_user_id')
    ->orderBy('id')
    ->limit(25)
    ->get() as $t) {
    printf(
        "%-4d %-12s %-6s %-6s %s\n",
        $t->id,
        $t->monday_item_id,
        $t->customer_user_id ?? '-',
        $t->assigned_user_id ?? '-',
        substr($t->subject ?? '', 0, 50)
    );
}
