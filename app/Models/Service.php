<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    protected $fillable = [
        'name',
        'description',
        'location',
        'price_per_hour',
        'price_per_day',
        'price_per_month',
        'capacity',
        'amenities',
        'availability_hours',
        'status',
        'customer_id',
        'user_id',
        'reserved_from',
        'reserved_until',
        'notes'
    ];

    protected $casts = [
        'amenities' => 'array',
        'availability_hours' => 'array',
        'reserved_from' => 'datetime',
        'reserved_until' => 'datetime',
        'price_per_hour' => 'decimal:2',
        'price_per_day' => 'decimal:2',
        'price_per_month' => 'decimal:2'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods
    public function isAvailable(): bool
    {
        return $this->status === 'active';
    }

    public function isReserved(): bool
    {
        return $this->status === 'reserved';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function getFormattedPriceAttribute(): string
    {
        $prices = [];
        if ($this->price_per_hour) $prices[] = '$' . $this->price_per_hour . '/hr';
        if ($this->price_per_day) $prices[] = '$' . $this->price_per_day . '/day';
        if ($this->price_per_month) $prices[] = '$' . $this->price_per_month . '/month';
        
        return implode(' | ', $prices);
    }

    public function getCurrentReservationInfo(): ?string
    {
        if ($this->isReserved() && $this->customer) {
            return "Reserved by {$this->customer->name} until " . 
                   $this->reserved_until?->format('M j, Y g:i A');
        }
        return null;
    }
}
