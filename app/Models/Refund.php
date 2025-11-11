<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'customer_id',
        'processed_by',
        'refund_amount',
        'original_amount_paid',
        'cancellation_fee',
        'refund_method',
        'status',
        'reason',
        'notes',
        'reference_number',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'refund_amount' => 'decimal:2',
        'original_amount_paid' => 'decimal:2',
        'cancellation_fee' => 'decimal:2',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Calculate refund amount based on cancellation policy
     * 
     * @param Reservation $reservation
     * @return array ['refund_amount' => float, 'cancellation_fee' => float, 'percentage' => float]
     */
    public static function calculateRefund(Reservation $reservation): array
    {
        $amountPaid = (float) $reservation->amount_paid;
        
        // If nothing paid, no refund
        if ($amountPaid <= 0) {
            return [
                'refund_amount' => 0,
                'cancellation_fee' => 0,
                'percentage' => 0,
            ];
        }

        // Calculate hours until reservation starts
        $now = Carbon::now();
        $startTime = Carbon::parse($reservation->start_time);
        $hoursUntilStart = $now->diffInHours($startTime, false);

        // Cancellation policy (Lenient):
        // - 12+ hours before: 100% refund (no fee)
        // - 6-12 hours before: 90% refund (10% fee)
        // - 3-6 hours before: 75% refund (25% fee)
        // - 1-3 hours before: 50% refund (50% fee)
        // - Less than 1 hour: 25% refund (75% fee)
        // - After start time: 0% refund (100% fee)

        $refundPercentage = 0;
        
        if ($hoursUntilStart >= 12) {
            $refundPercentage = 100; // Full refund
        } elseif ($hoursUntilStart >= 6) {
            $refundPercentage = 90;
        } elseif ($hoursUntilStart >= 3) {
            $refundPercentage = 75;
        } elseif ($hoursUntilStart >= 1) {
            $refundPercentage = 50;
        } elseif ($hoursUntilStart > 0) {
            $refundPercentage = 25;
        } else {
            $refundPercentage = 0; // Reservation already started
        }

        $refundAmount = round(($amountPaid * $refundPercentage) / 100, 2);
        $cancellationFee = round($amountPaid - $refundAmount, 2);

        return [
            'refund_amount' => $refundAmount,
            'cancellation_fee' => $cancellationFee,
            'percentage' => $refundPercentage,
            'hours_until_start' => $hoursUntilStart,
        ];
    }

    /**
     * Generate a unique reference number for the refund
     */
    public static function generateReferenceNumber(): string
    {
        return 'REF-' . strtoupper(uniqid()) . '-' . rand(1000, 9999);
    }
}
