<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'user_id',
        'name',
        'company_name',
        'contact_person',
        'email',
        'phone',
        'address',
        'website',
        'status',
        'notes',
        'amount_paid',
        'space_type_id',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
    ];

    // Get formatted amount paid
    public function getFormattedAmountPaidAttribute()
    {
        return $this->amount_paid ? '₱' . number_format($this->amount_paid, 2) : '₱0.00';
    }

    // Removed tasks() relationship (Task Tracker deprecated)
    // public function tasks(): HasMany { return $this->hasMany(Task::class); }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function assignedSpace()
    {
        return $this->hasOne(Space::class, 'current_customer_id');
    }

    public function spaceType()
    {
        return $this->belongsTo(SpaceType::class, 'space_type_id');
    }
}
