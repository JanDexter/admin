<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    protected $table = 'staff';

    protected $fillable = [
        'user_id',
        'employee_id',
        'department',
        'hourly_rate',
        'hired_date',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'hired_date' => 'date',
    ];

    /**
     * Get the user that owns this staff profile
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get reservations processed by this staff member
     */
    public function processedReservations()
    {
        return $this->hasMany(Reservation::class, 'created_by', 'user_id');
    }

    /**
     * Get transaction logs processed by this staff member
     */
    public function processedTransactions()
    {
        return $this->hasMany(TransactionLog::class, 'processed_by', 'user_id');
    }

    /**
     * Get formatted hourly rate
     */
    public function getFormattedHourlyRateAttribute()
    {
        return $this->hourly_rate ? '₱' . number_format($this->hourly_rate, 2) : 'N/A';
    }
}
