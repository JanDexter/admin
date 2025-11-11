<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_active',
        'email_verified_at', // Added to allow setting this field when creating users
        'google_id',
        'avatar',
    ];

    /**
     * Check if user has a specific role (by checking specialized tables)
     */
    public function hasRole($role)
    {
        return match($role) {
            'admin' => $this->admin !== null,
            'staff' => $this->staff !== null,
            'customer' => $this->customer !== null,
            default => false
        };
    }

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->admin !== null;
    }

    /**
     * Check if user is staff
     */
    public function isStaff()
    {
        return $this->staff !== null;
    }

    /**
     * Check if user is customer
     */
    public function isCustomer()
    {
        return $this->customer !== null;
    }

    /**
     * Get all roles for this user
     */
    public function getRoles(): array
    {
        $roles = [];
        if ($this->admin) $roles[] = 'admin';
        if ($this->staff) $roles[] = 'staff';
        if ($this->customer) $roles[] = 'customer';
        return $roles;
    }

    /**
     * Get primary role (first available in priority order: admin > staff > customer)
     */
    public function getPrimaryRole(): ?string
    {
        if ($this->admin) return 'admin';
        if ($this->staff) return 'staff';
        if ($this->customer) return 'customer';
        return null;
    }

    /**
     * Get formatted role name
     */
    public function getFormattedRoleAttribute()
    {
        $roles = $this->getRoles();
        if (empty($roles)) return 'No Role';
        
        return implode(', ', array_map('ucfirst', $roles));
    }

    /**
     * Get role color for UI display (based on primary role)
     */
    public function getRoleColorAttribute()
    {
        return match($this->getPrimaryRole()) {
            'admin' => 'bg-red-100 text-red-800',
            'staff' => 'bg-blue-100 text-blue-800',
            'customer' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the customer record associated with this user (if they are a customer)
     */
    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    /**
     * Get the staff record associated with this user (if they are staff)
     */
    public function staff()
    {
        return $this->hasOne(Staff::class);
    }

    /**
     * Get the admin record associated with this user (if they are admin)
     */
    public function admin()
    {
        return $this->hasOne(Admin::class);
    }

    /**
     * Get the active profile (admin, staff, or customer)
     */
    public function getActiveProfile()
    {
        return $this->admin ?? $this->staff ?? $this->customer;
    }

    /**
     * Removed tasks() relationship (Task Tracker deprecated)
     */
    // public function tasks() { return $this->hasMany(Task::class); }

    /**
     * Get services managed by this user
     */
    public function services()
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Get reservations made by this user
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Check if user can book spaces
     */
    public function canBookSpaces(): bool
    {
        // Customers can always book
        if ($this->isCustomer()) {
            return true;
        }

        // Admins and staff can book only if they have customer profile
        return $this->customer !== null;
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        // Super admin has all permissions
        if ($this->admin && $this->admin->isSuperAdmin()) {
            return true;
        }

        // Check admin permissions
        if ($this->admin) {
            return $this->admin->hasPermission($permission);
        }

        // Staff has limited permissions
        if ($this->staff) {
            return in_array($permission, Permission::getPresetPermissions('staff'));
        }

        // Customer has basic permissions
        if ($this->customer) {
            return in_array($permission, Permission::getPresetPermissions('customer'));
        }

        return false;
    }

    /**
     * Get all permissions for this user
     */
    public function getAllPermissions(): array
    {
        if ($this->admin) {
            return $this->admin->getAllPermissions();
        }

        if ($this->staff) {
            return Permission::getPresetPermissions('staff');
        }

        if ($this->customer) {
            return Permission::getPresetPermissions('customer');
        }

        return [];
    }
}
