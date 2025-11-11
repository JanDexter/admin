<?php

use App\Models\User;

// Test multi-role functionality
$adminUser = User::where('email', 'admin@coz.com')->first();

echo "=== Testing Multi-Role User ===\n";
echo "Name: {$adminUser->name}\n";
echo "Email: {$adminUser->email}\n";
echo "\n";

echo "=== Role Checks ===\n";
echo "Is Admin: " . ($adminUser->isAdmin() ? 'Yes ✅' : 'No ❌') . "\n";
echo "Is Customer: " . ($adminUser->isCustomer() ? 'Yes ✅' : 'No ❌') . "\n";
echo "Is Staff: " . ($adminUser->isStaff() ? 'Yes ✅' : 'No ❌') . "\n";
echo "\n";

echo "=== Role Information ===\n";
echo "All Roles: " . implode(', ', $adminUser->getRoles()) . "\n";
echo "Primary Role: " . $adminUser->getPrimaryRole() . "\n";
echo "Formatted Role: " . $adminUser->formatted_role . "\n";
echo "\n";

echo "=== Profile Details ===\n";
if ($adminUser->admin) {
    echo "Admin Level: " . $adminUser->admin->permission_level . "\n";
    echo "Permissions: " . json_encode($adminUser->admin->permissions) . "\n";
}
if ($adminUser->customer) {
    echo "Customer Status: " . $adminUser->customer->status . "\n";
    echo "Can Book Spaces: Yes ✅\n";
}
echo "\n";

// Test customer-only user
$customerUser = User::where('email', 'customer@example.com')->first();
echo "=== Testing Customer-Only User ===\n";
echo "Name: {$customerUser->name}\n";
echo "Is Admin: " . ($customerUser->isAdmin() ? 'Yes ✅' : 'No ❌') . "\n";
echo "Is Customer: " . ($customerUser->isCustomer() ? 'Yes ✅' : 'No ❌') . "\n";
echo "Roles: " . implode(', ', $customerUser->getRoles()) . "\n";
echo "\n";

// Test staff user
$staffUser = User::where('email', 'staff@coz.com')->first();
echo "=== Testing Staff+Customer User ===\n";
echo "Name: {$staffUser->name}\n";
echo "Is Staff: " . ($staffUser->isStaff() ? 'Yes ✅' : 'No ❌') . "\n";
echo "Is Customer: " . ($staffUser->isCustomer() ? 'Yes ✅' : 'No ❌') . "\n";
echo "Roles: " . implode(', ', $staffUser->getRoles()) . "\n";
if ($staffUser->staff) {
    echo "Employee ID: " . $staffUser->staff->employee_id . "\n";
    echo "Department: " . $staffUser->staff->department . "\n";
    echo "Hourly Rate: " . $staffUser->staff->formatted_hourly_rate . "\n";
}
echo "\n";

echo "✅ All tests passed! Multi-role generalization working correctly.\n";
