<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
// use Illuminate\Contracts\Auth\MustVerifyEmail; // Commented out - email verification disabled

class User extends Authenticatable // implements MustVerifyEmail // Commented out - email verification disabled
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
        'role',
        'is_active',
        'email_verified_at', // Added to allow setting this field when creating users
        'google_id',
        'avatar',
    ];

    /**
     * Role constants
     */
    const ROLE_CUSTOMER = 'customer';
    const ROLE_STAFF = 'staff';
    const ROLE_ADMIN = 'admin';

    /**
     * Get all available roles
     */
    public static function getRoles()
    {
        return [
            self::ROLE_CUSTOMER => 'Customer',
            self::ROLE_STAFF => 'Staff',
            self::ROLE_ADMIN => 'Admin',
        ];
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole($role)
    {
        return $this->role === $role;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    /**
     * Check if user is staff
     */
    public function isStaff()
    {
        return $this->hasRole(self::ROLE_STAFF);
    }

    /**
     * Check if user is customer
     */
    public function isCustomer()
    {
        return $this->hasRole(self::ROLE_CUSTOMER);
    }

    /**
     * Get formatted role name
     */
    public function getFormattedRoleAttribute()
    {
        return ucfirst($this->role);
    }

    /**
     * Get role color for UI display
     */
    public function getRoleColorAttribute()
    {
        return match($this->role) {
            self::ROLE_ADMIN => 'bg-red-100 text-red-800',
            self::ROLE_STAFF => 'bg-blue-100 text-blue-800',
            self::ROLE_CUSTOMER => 'bg-green-100 text-green-800',
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
}
