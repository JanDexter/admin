<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_id',
        'space_id',
        'start_time',
        'end_time',
        'cost',
        'custom_hourly_rate',
    'applied_hourly_rate',
    'applied_discount_hours',
    'applied_discount_percentage',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    'applied_hourly_rate' => 'decimal:2',
    'applied_discount_hours' => 'integer',
    'applied_discount_percentage' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function space()
    {
        return $this->belongsTo(Space::class);
    }
}
