<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
        'photo_path',
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

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
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

    /**
     * Get real-time available capacity for a specific time window.
     * Returns the number of slots that can still be booked.
     */
    public function getAvailableCapacity(\Carbon\Carbon $start, ?\Carbon\Carbon $end = null, ?int $excludeReservationId = null)
    {
        $totalCapacity = (int) ($this->total_slots ?? 0);
        $spaceUnits = $this->spaces()->count();

        $baseQuery = \App\Models\Reservation::query()
            ->active()
            ->where(function ($query) {
                $query->where('space_type_id', $this->id)
                    ->orWhereHas('space', function ($spaceQuery) {
                        $spaceQuery->where('space_type_id', $this->id);
                    });
            })
            ->when($excludeReservationId, function ($query) use ($excludeReservationId) {
                $query->where('id', '!=', $excludeReservationId);
            })
            ->overlapping($start, $end);

        $seatBasedCapacity = null;
        if ($totalCapacity > 0) {
            $overlappingPax = (clone $baseQuery)->sum(DB::raw('CASE WHEN pax IS NULL OR pax < 1 THEN 1 ELSE pax END'));
            $seatBasedCapacity = max(0, $totalCapacity - $overlappingPax);
        }

        $spaceBasedCapacity = null;
        if ($spaceUnits > 0) {
            $occupiedUnits = (clone $baseQuery)->count();
            $spaceBasedCapacity = max(0, $spaceUnits - $occupiedUnits);
        }

        if (!is_null($seatBasedCapacity) && !is_null($spaceBasedCapacity)) {
            return min($seatBasedCapacity, $spaceBasedCapacity);
        }

        if (!is_null($seatBasedCapacity)) {
            return $seatBasedCapacity;
        }

        if (!is_null($spaceBasedCapacity)) {
            return $spaceBasedCapacity;
        }

        return 0;
    }
}
