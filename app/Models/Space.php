<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Space extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_type_id',
        'name',
        'status',
        'current_customer_id',
        'occupied_from',
        'occupied_until',
        'notes',
        'hourly_rate',
        'discount_hours',
        'discount_percentage',
        'custom_rates',
    ];

    protected $casts = [
        'occupied_from' => 'datetime',
        'occupied_until' => 'datetime',
        'custom_rates' => 'array',
    ];

    public function spaceType()
    {
        return $this->belongsTo(SpaceType::class);
    }

    public function currentCustomer()
    {
        return $this->belongsTo(Customer::class, 'current_customer_id');
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'available' => 'bg-green-100 text-green-800',
            'occupied' => 'bg-red-100 text-red-800',
            'maintenance' => 'bg-yellow-100 text-yellow-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function isAvailable()
    {
        return $this->status === 'available' && !$this->current_customer_id;
    }

    public function occupy($customerId, $from = null, $until = null)
    {
        $this->update([
            'status' => 'occupied',
            'current_customer_id' => $customerId,
            'occupied_from' => $from ?? now(),
            'occupied_until' => $until,
        ]);

        // Update space type available slots
        $this->spaceType->decrement('available_slots');
    }

    public function release()
    {
        $this->update([
            'status' => 'available',
            'current_customer_id' => null,
            'occupied_from' => null,
            'occupied_until' => null,
        ]);

        // Update space type available slots
        $this->spaceType->increment('available_slots');
    }

    public function getEffectiveHourlyRate()
    {
        return $this->hourly_rate ?? $this->spaceType->hourly_rate ?? $this->spaceType->default_price;
    }

    public function calculateCost($hours)
    {
        $hourlyRate = $this->getEffectiveHourlyRate();
        $discountHours = $this->discount_hours ?? $this->spaceType->default_discount_hours;
        $discountPercentage = $this->discount_percentage ?? $this->spaceType->default_discount_percentage;

        $totalCost = $hourlyRate * $hours;

        // Apply discount if applicable
        if ($discountHours && $discountPercentage && $hours >= $discountHours) {
            $discountAmount = ($totalCost * $discountPercentage) / 100;
            $totalCost -= $discountAmount;
        }

        return $totalCost;
    }

    public function getTimeUntilFree()
    {
        if ($this->status !== 'occupied' || !$this->occupied_until) {
            return null;
        }

        $now = now();
        $until = $this->occupied_until;

        if ($until <= $now) {
            return 'Available now';
        }

        $diff = $until->diff($now);
        
        if ($diff->d > 0) {
            return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ' . $diff->h . 'h';
        } elseif ($diff->h > 0) {
            return $diff->h . 'h ' . $diff->i . 'm';
        } else {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
        }
    }

    public function isExpiringSoon($hours = 1)
    {
        if ($this->status !== 'occupied' || !$this->occupied_until) {
            return false;
        }

        return $this->occupied_until <= now()->addHours($hours);
    }
}
