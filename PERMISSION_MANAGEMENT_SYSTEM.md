# Permission Management System

## Overview

The CO-Z Co-Workspace system now has a comprehensive permission management system that allows administrators to control access and capabilities for all users through a granular, role-based approach.

## Key Features

### ✅ Role-Based Access Control
- Users can have multiple roles simultaneously (Admin, Staff, Customer)
- Each role provides specific capabilities
- Roles can be added/removed independently

### ✅ Granular Permissions
- 30+ individual permissions across 7 categories
- Toggle individual permissions for fine-grained control
- Preset templates for quick setup

### ✅ Booking Control
- **By Default**: Admins and Staff CANNOT book spaces
- **To Enable Booking**: Add "Customer" role to admin/staff users
- Warning indicator shown when admin/staff lacks customer role

### ✅ Permission Presets
Three built-in templates:
1. **Administrator** - Full system access (except deletion)
2. **Staff Member** - Operational access for front desk
3. **Customer** - Basic booking and viewing access

## Permission Categories

### 1. User Management
- `view_users` - View all users
- `create_users` - Create new users
- `edit_users` - Edit user details
- `delete_users` - Delete users
- `manage_user_roles` - Manage roles and permissions
- `activate_deactivate_users` - Activate/deactivate users

### 2. Space Management
- `view_spaces` - View all spaces
- `create_spaces` - Create new spaces
- `edit_spaces` - Edit space details
- `delete_spaces` - Delete spaces
- `manage_space_types` - Manage space types and pricing
- `view_space_availability` - View space availability

### 3. Reservation Management
- `view_all_reservations` - View all reservations
- `create_reservations` - Create reservations for customers
- `edit_reservations` - Edit reservation details
- `cancel_reservations` - Cancel reservations
- `extend_reservations` - Extend reservation time
- `override_pricing` - Override reservation pricing

### 4. Customer Management
- `view_customers` - View all customers
- `create_customers` - Create new customers
- `edit_customers` - Edit customer details
- `view_customer_history` - View customer transaction history
- `book_spaces` - Book spaces (as customer)

### 5. Financial
- `view_transactions` - View all transactions
- `process_payments` - Process payments
- `issue_refunds` - Issue refunds
- `approve_refunds` - Approve refund requests
- `view_financial_reports` - View financial reports
- `export_financial_data` - Export financial data

### 6. Reports
- `view_dashboard` - View dashboard analytics
- `view_reports` - View system reports
- `export_reports` - Export reports
- `view_analytics` - View advanced analytics

### 7. Settings
- `manage_system_settings` - Manage system settings
- `view_logs` - View system logs
- `backup_restore` - Backup and restore data

## Admin Levels

### Super Administrator
- **ALL permissions** automatically granted
- Cannot be restricted
- Ideal for system owners

### Administrator
- Customizable permissions
- Choose which capabilities to grant
- Can be tailored per user

### Moderator
- Limited administrative access
- Preset permission set
- Good for trusted users

## Usage Guide

### Accessing Permission Management

1. Navigate to **User Management** from admin dashboard
2. Click on any user to view details
3. Click **"Manage Permissions"** button (purple)

### Setting Up a New Admin

**Scenario**: Add new administrator who can manage bookings but not finances

1. Go to User → Manage Permissions
2. Check "Administrator" role
3. Select "Administrator" level (not Super Admin)
4. Toggle specific permissions:
   - ✅ Enable: All space & reservation permissions
   - ❌ Disable: Financial permissions (refunds, exports)
5. Click "Save Changes"

### Allowing Admin to Book Spaces

**Scenario**: Admin wants to reserve a workspace for themselves

1. Go to User → Manage Permissions
2. Check BOTH "Administrator" AND "Customer" roles
3. Click "Save Changes"
4. Admin can now:
   - Access admin dashboard
   - Book spaces as customer
   - View their own reservations

### Quick Setup with Presets

1. Go to User → Manage Permissions
2. Click "Apply Preset" button
3. Choose template:
   - **Administrator** - Full access user
   - **Staff Member** - Front desk employee
   - **Customer** - Regular customer
4. Confirm (WARNING: This replaces all current roles!)

## API Usage

### Check User Permissions (PHP)

```php
$user = User::find(1);

// Check specific permission
if ($user->hasPermission('approve_refunds')) {
    // User can approve refunds
}

// Check role
if ($user->isAdmin()) {
    // User is an administrator
}

// Check if user can book
if ($user->canBookSpaces()) {
    // User has customer role
}

// Get all permissions
$permissions = $user->getAllPermissions();
```

### Grant/Revoke Permissions

```php
$admin = $user->admin;

// Grant permission
$admin->grantPermission('delete_users');

// Revoke permission
$admin->revokePermission('delete_users');

// Apply preset
$admin->setPresetPermissions('admin');
```

## Routes

### Permission Management Routes
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/user-management/{user}/permissions` | Edit permissions page |
| PUT | `/user-management/{user}/permissions` | Update permissions |
| POST | `/user-management/{user}/permissions/preset` | Apply preset template |
| POST | `/user-management/{user}/permissions/toggle` | Toggle single permission |

## Database Schema

### `admins` Table
```sql
- id (PK)
- user_id (FK → users.id, unique)
- permission_level (enum: super_admin, admin, moderator)
- permissions (JSON array of permission slugs)
- timestamps
```

### Example Permission Data
```json
{
  "permissions": [
    "view_users",
    "create_users",
    "edit_users",
    "view_spaces",
    "create_reservations",
    "process_payments"
  ]
}
```

## Security Notes

### ✅ Best Practices
1. **Least Privilege**: Only grant permissions users need
2. **Regular Audits**: Review user permissions quarterly
3. **Super Admin Sparingly**: Limit super admin accounts
4. **Role Separation**: Don't give all users all roles

### ⚠️ Important Restrictions
1. **Super Admins**: Cannot have permissions restricted
2. **Deletion**: Requires explicit `delete_users` permission
3. **Financial**: Refund approval separate from issuing
4. **Booking**: Requires customer role, not just permission

## Common Use Cases

### Case 1: Front Desk Staff
**Setup**: Staff preset
**Can**:
- View all spaces
- Create reservations
- Process payments
- View customers

**Cannot**:
- Delete anything
- Manage users
- Approve refunds
- Book spaces (unless given customer role)

### Case 2: Manager Who Books
**Setup**: Admin + Customer roles
**Can**:
- Full admin access
- Book personal workspace
- See own reservations
- Everything an admin can do

### Case 3: Accountant
**Setup**: Custom admin with financial permissions only
**Can**:
- View transactions
- Process payments
- Approve refunds
- Export financial reports

**Cannot**:
- Manage users
- Create spaces
- Delete data

## Troubleshooting

### Admin Can't Book Spaces
**Solution**: Add "Customer" role to admin user

### Permissions Not Saving
**Check**:
1. User is not Super Admin (cannot restrict)
2. Database connection is active
3. User has `manage_user_roles` permission

### Preset Doesn't Show All Permissions
**Reason**: Presets are templates, not full permission lists
**Solution**: Apply preset, then customize individual permissions

## Migration Notes

### Existing Users
- All existing admins automatically migrated
- Default admin level: `super_admin`
- No permission restrictions initially

### Backward Compatibility
- Old role checks still work: `$user->isAdmin()`
- New permission checks available: `$user->hasPermission()`
- No breaking changes to existing code

## Future Enhancements

1. **Permission Groups**: Bundle related permissions
2. **Time-based Permissions**: Temporary access grants
3. **Audit Logging**: Track permission changes
4. **API Tokens**: Permission-scoped API access
5. **Custom Roles**: User-defined role templates

---

**Version**: 1.0
**Last Updated**: November 10, 2025
**Author**: CO-Z Development Team
