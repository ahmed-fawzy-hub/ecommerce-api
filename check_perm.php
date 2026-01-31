<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $perms = Spatie\Permission\Models\Permission::all();
    echo "Permissions found:\n";
    foreach($perms as $p) {
        echo "- " . $p->name . " (" . $p->guard_name . ")\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
