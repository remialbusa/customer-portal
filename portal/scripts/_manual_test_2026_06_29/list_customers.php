<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (\App\Models\User::where('role', 'customer')->orderBy('id')->get(['id','email','name','status']) as $u) {
    printf("%d | %s | %s | %s\n", $u->id, $u->email, $u->name, $u->status);
}
echo "---\n";
foreach (\App\Models\User::whereIn('role', ['fse','its','manager'])->orderBy('id')->get(['id','email','name','role','status']) as $u) {
    printf("%d | %s | %s | %s | %s\n", $u->id, $u->email, $u->name, $u->role, $u->status);
}
echo "---\n";
foreach (\App\Models\User::whereIn('role', ['admin','superadmin'])->orderBy('id')->get(['id','email','name','role','status']) as $u) {
    printf("%d | %s | %s | %s | %s\n", $u->id, $u->email, $u->name, $u->role, $u->status);
}
