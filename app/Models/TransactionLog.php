<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'reservation_id',
        'customer_id',
        'processed_by',
        'amount',
        'payment_method',
        'status',
        'reference_number',
        'description',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the reservation associated with this transaction
     */
    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Get the customer associated with this transaction
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user who processed this transaction
     */
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Generate a unique reference number
     */
    public static function generateReferenceNumber(string $type): string
    {
        $prefix = match($type) {
            'payment' => 'PAY',
            'refund' => 'REF',
            'cancellation' => 'CAN',
            default => 'TXN',
        };
        
        return $prefix . '-' . strtoupper(uniqid());
    }

    /**
     * Create a payment transaction log
     */
    public static function logPayment(Reservation $reservation, float $amount, ?int $processedBy = null, ?string $notes = null): self
    {
        return self::create([
            'type' => 'payment',
            'reservation_id' => $reservation->id,
            'customer_id' => $reservation->customer_id,
            'processed_by' => $processedBy,
            'amount' => $amount,
            'payment_method' => $reservation->payment_method,
            'status' => 'completed',
            'reference_number' => self::generateReferenceNumber('payment'),
            'description' => "Payment for reservation #{$reservation->id}",
            'notes' => $notes,
        ]);
    }

    /**
     * Create a refund transaction log
     */
    public static function logRefund(Reservation $reservation, float $amount, ?int $processedBy = null, ?string $notes = null): self
    {
        return self::create([
            'type' => 'refund',
            'reservation_id' => $reservation->id,
            'customer_id' => $reservation->customer_id,
            'processed_by' => $processedBy,
            'amount' => -$amount, // Negative amount for refunds
            'payment_method' => $reservation->payment_method,
            'status' => 'completed',
            'reference_number' => self::generateReferenceNumber('refund'),
            'description' => "Refund for cancelled reservation #{$reservation->id}",
            'notes' => $notes,
        ]);
    }

    /**
     * Create a cancellation transaction log
     */
    public static function logCancellation(Reservation $reservation, ?int $processedBy = null, ?string $notes = null): self
    {
        return self::create([
            'type' => 'cancellation',
            'reservation_id' => $reservation->id,
            'customer_id' => $reservation->customer_id,
            'processed_by' => $processedBy,
            'amount' => 0,
            'payment_method' => null,
            'status' => 'completed',
            'reference_number' => self::generateReferenceNumber('cancellation'),
            'description' => "Reservation #{$reservation->id} cancelled",
            'notes' => $notes,
        ]);
    }
}
