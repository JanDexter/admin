<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Checking user authorization...\n";

try {
    $admin = App\Models\User::where('email', 'admin@admin.com')->first();
    
    if ($admin) {
        echo "Admin user found:\n";
        echo "Email: {$admin->email}\n";
        echo "Role: {$admin->role}\n";
        echo "Is Admin: " . ($admin->isAdmin() ? 'Yes' : 'No') . "\n";
        echo "Active: " . ($admin->is_active ? 'Yes' : 'No') . "\n";
        
        // Check authorization
        $gate = app(\Illuminate\Auth\Access\Gate::class);
        echo "Can access admin features: " . ($gate->forUser($admin)->allows('admin-access') ? 'Yes' : 'No') . "\n";
        echo "Can manage users: " . ($gate->forUser($admin)->allows('manage-users') ? 'Yes' : 'No') . "\n";
        
    } else {
        echo "Admin user NOT found!\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
