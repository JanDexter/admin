<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpaceType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'default_price',
        'hourly_rate',
        'default_discount_hours',
        'default_discount_percentage',
        'total_slots',
        'available_slots',
        'description',
    ];

    protected $casts = [
        'default_price' => 'decimal:2',
        'total_slots' => 'integer',
        'available_slots' => 'integer',
    ];

    public function spaces()
    {
        return $this->hasMany(Space::class);
    }

    public function customers()
    {
        return $this->hasManyThrough(Customer::class, Space::class, 'space_type_id', 'id', 'id', 'current_customer_id');
    }

    public function getFormattedPriceAttribute()
    {
        return '₱' . number_format($this->default_price, 2);
    }

    public function getOccupancyRateAttribute()
    {
        return $this->total_slots > 0 
            ? round((($this->total_slots - $this->available_slots) / $this->total_slots) * 100, 1)
            : 0;
    }

    public static function getDefaultSpaceTypes()
    {
        return [
            ['name' => 'PRIVATE SPACE', 'total_slots' => 8, 'default_price' => 50.00, 'hourly_rate' => 50.00, 'default_discount_hours' => 4, 'default_discount_percentage' => 10.00],
            ['name' => 'DRAFTING TABLE', 'total_slots' => 3, 'default_price' => 50.00, 'hourly_rate' => 50.00, 'default_discount_hours' => 6, 'default_discount_percentage' => 15.00],
            ['name' => 'CONFERENCE ROOM', 'total_slots' => 1, 'default_price' => 350.00, 'hourly_rate' => 350.00, 'default_discount_hours' => 3, 'default_discount_percentage' => 20.00],
            ['name' => 'SHARED SPACE', 'total_slots' => 34, 'default_price' => 40.00, 'hourly_rate' => 40.00, 'default_discount_hours' => 8, 'default_discount_percentage' => 12.00],
            ['name' => 'EXCLUSIVE SPACE', 'total_slots' => 6, 'default_price' => 60.00, 'hourly_rate' => 60.00, 'default_discount_hours' => 5, 'default_discount_percentage' => 18.00],
        ];
    }

    public function getOccupancyFraction()
    {
        $total = $this->spaces()->count();
        $occupied = $this->spaces()->where('status', 'occupied')->count();
        return "{$occupied}/{$total}";
    }

    public function getOccupancyPercentage()
    {
        $total = $this->spaces()->count();
        if ($total === 0) return 0;
        $occupied = $this->spaces()->where('status', 'occupied')->count();
        return round(($occupied / $total) * 100);
    }

    public function getNextAvailableTime()
    {
        $occupiedSpaces = $this->spaces()
            ->where('status', 'occupied')
            ->whereNotNull('occupied_until')
            ->orderBy('occupied_until', 'asc')
            ->first();

        if (!$occupiedSpaces) {
            return null; // No occupied spaces with end time
        }

        return $occupiedSpaces->occupied_until;
    }

    public function getAvailableSpaces()
    {
        return $this->spaces()->where('status', 'available')->count();
    }

    public function getOccupiedSpaces()
    {
        return $this->spaces()->where('status', 'occupied')->count();
    }
}
