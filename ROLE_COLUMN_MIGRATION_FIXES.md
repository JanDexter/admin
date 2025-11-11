# Role Column Migration Fixes

## Overview
After implementing the user role generalization system (removing the `role` enum column from the `users` table), several files needed to be updated to use the new relationship-based role checking system.

## Date
2025-11-10

## Problem
The migration `2025_11_10_164618_remove_role_from_users_table.php` removed the `role` column, but legacy code was still querying this column, causing SQL errors:
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'role' in 'where clause'
```

## Solution Pattern

### Database Queries
**OLD:**
```php
User::where('role', 'admin')->get()
User::where('role', User::ROLE_CUSTOMER)->count()
```

**NEW:**
```php
User::whereHas('admin')->get()
User::whereHas('customer')->count()
```

### User Role Checks
**OLD:**
```php
if ($user->role === 'admin') { }
if ($user->role === User::ROLE_CUSTOMER) { }
```

**NEW:**
```php
if ($user->isAdmin()) { }
if ($user->isCustomer()) { }
```

### Creating Users with Roles
**OLD:**
```php
User::create([
    'name' => 'John',
    'email' => 'john@example.com',
    'role' => 'customer',
]);
```

**NEW:**
```php
DB::beginTransaction();
try {
    $user = User::create([
        'name' => 'John',
        'email' => 'john@example.com',
    ]);
    
    // Create the appropriate role record
    Customer::create(['user_id' => $user->id]);
    
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
}
```

## Files Modified

### Controllers

1. **app/Http/Controllers/UserManagementController.php**
   - Updated `index()`: Changed role filtering to use `whereHas()`
   - Updated `index()`: Transformed users to include `role_type` attribute
   - Updated `store()`: Added DB transaction for creating User + role records
   - Updated `update()`: Added role change logic with DB transaction
   - Updated `show()`: Added `role_type` to user data
   - Updated `edit()`: Added `role_type` to user data
   - Added imports: `Admin`, `Staff`, `Customer`, `DB`

2. **app/Http/Controllers/DashboardController.php**
   - Line 22: Changed `User::where('role', 'customer')` to `User::whereHas('customer')`

3. **app/Http/Controllers/SetupController.php**
   - Lines 15, 25: Changed admin checks to `whereHas('admin')`
   - Lines 37-56: Added Admin model creation with DB transaction

4. **app/Http/Controllers/Auth/RegisteredUserController.php**
   - Added `Admin` model import
   - Removed `role` from User::create()
   - Added logic: first user becomes super_admin, others become customers

5. **app/Http/Controllers/Auth/VerifyEmailController.php**
   - Line 36: Changed to `$user->isCustomer()`

6. **app/Http/Controllers/Auth/OtpVerificationController.php**
   - Line 57: Changed to `$user->isCustomer()`

7. **app/Http/Controllers/ProfileController.php**
   - Line 43: Changed to `$user->isCustomer()`

8. **app/Http/Controllers/CustomerViewController.php**
   - Line 19: Changed `$user->role === User::ROLE_CUSTOMER` to `$user->isCustomer()`

### Middleware

9. **app/Http/Middleware/EnsureAdminExists.php**
   - Line 25: Changed to `User::whereHas('admin')->count()`

10. **app/Http/Middleware/HandleInertiaRequests.php**
    - Updated `share()` to include role flags: `is_admin`, `is_staff`, `is_customer`, `role_type`

### Seeders

11. **database/seeders/AdminSeeder.php**
    - Removed `role` from User::firstOrCreate()
    - Added Admin model import and DB facade
    - Added Admin record creation with DB transaction
    - Set `permission_level` to `super_admin`

### Vue Components

12. **resources/js/Layouts/AuthenticatedLayout.vue**
    - Lines 37, 160, 169: Changed `$page.props.auth.user.role === 'admin'` to `$page.props.auth.user.is_admin`

13. **resources/js/Pages/UserManagement/Show.vue**
    - Lines 127-128: Changed `user.role` to `user.role_type`
    - Lines 177, 187: Changed `user.role === 'admin'` to `user.role_type === 'admin'`

14. **resources/js/Pages/UserManagement/Index.vue**
    - Lines 295-296: Changed `user.role` to `user.role_type`

15. **resources/js/Pages/UserManagement/Edit.vue**
    - Line 19: Changed `props.user.role` to `props.user.role_type`

16. **resources/js/Pages/Dashboard.vue**
    - Line 311: Changed `$page.props.auth.user.role === 'admin'` to `$page.props.auth.user.is_admin`

## User Model Changes (Already Implemented)

The `User` model was already updated with helper methods:
- `isAdmin()`: Checks if user has an admin record
- `isStaff()`: Checks if user has a staff record  
- `isCustomer()`: Checks if user has a customer record

## Database Structure

The new structure follows the ISA (inheritance) pattern:

```
users (base table)
├── admins (1:1 via user_id FK)
├── staff (1:1 via user_id FK)
└── customers (1:1 via user_id FK)
```

## Testing Checklist

- [x] Controllers updated
- [x] Middleware updated
- [x] Seeders updated
- [x] Vue components updated
- [x] Frontend built successfully
- [ ] Manual testing: User registration (first user)
- [ ] Manual testing: User registration (subsequent users)
- [ ] Manual testing: Admin user management
- [ ] Manual testing: Role assignment
- [ ] Manual testing: Permission system

## Commands Run

```bash
# Build frontend
npm run build

# If needed, clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

## Related Documentation

- `USER_ROLE_GENERALIZATION.md` - Original implementation
- `PERMISSION_MANAGEMENT_SYSTEM.md` - Permission system docs
- Migration: `database/migrations/2025_11_10_164618_remove_role_from_users_table.php`

## Notes

- The `role` field is still accepted in form requests (`$request->role`) but is converted to the appropriate relationship record
- Multi-role support is maintained (users can have Admin + Customer, Staff + Customer, etc.)
- First user registration automatically creates a super_admin
- The `role_type` attribute is added dynamically in controllers for frontend display
- All role changes use DB transactions to ensure data consistency
