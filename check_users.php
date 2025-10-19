<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Checking users in the database...\n";

try {
    $users = App\Models\User::all();
    echo "Total users found: " . $users->count() . "\n";
    
    foreach ($users as $user) {
        echo "User: {$user->email}, Role: {$user->role}, Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
    }
    
    $admin = App\Models\User::where('email', 'admin@admin.com')->first();
    if ($admin) {
        echo "\nAdmin user details:\n";
        echo "Email: {$admin->email}\n";
        echo "Role: {$admin->role}\n";
        echo "Active: " . ($admin->is_active ? 'Yes' : 'No') . "\n";
        echo "Email verified: " . ($admin->email_verified_at ? 'Yes' : 'No') . "\n";
    } else {
        echo "\nAdmin user admin@admin.com NOT found!\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
