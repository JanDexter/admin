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
        'company_name',
        'contact_person',
        'email',
        'phone',
        'address',
        'website',
        'status',
        'notes',
        'service_type',
        'service_price',
        'service_start_time',
        'service_end_time',
        'amount_paid'
    ];

    protected $casts = [
        'service_start_time' => 'datetime',
        'service_end_time' => 'datetime',
        'service_price' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    // Service types with their prices
    public static function getServiceTypes()
    {
        return [
            'CONFERENCE ROOM' => 350,
            'SHARED SPACE' => 40,
            'EXCLUSIVE SPACE' => 60,
            'PRIVATE SPACE' => 50,
            'DRAFTING TABLE' => 50,
        ];
    }

    // Get formatted service price
    public function getFormattedServicePriceAttribute()
    {
        return $this->service_price ? '₱' . number_format($this->service_price, 2) : null;
    }

    // Get formatted amount paid
    public function getFormattedAmountPaidAttribute()
    {
        return $this->amount_paid ? '₱' . number_format($this->amount_paid, 2) : '₱0.00';
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
