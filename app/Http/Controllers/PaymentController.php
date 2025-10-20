<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function processPayment(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,gcash,maya,card',
            'notes' => 'nullable|string|max:500',
        ]);

        $amount = (float) $validated['amount'];
        $totalCost = $reservation->total_cost;
        $currentPaid = (float) ($reservation->amount_paid ?? 0);
        $remaining = $totalCost - $currentPaid;

        // Validate payment amount doesn't exceed remaining balance
        if ($amount > $remaining) {
            return back()->withErrors(['amount' => "Payment amount cannot exceed remaining balance of ₱" . number_format($remaining, 2)]);
        }

        DB::transaction(function () use ($reservation, $validated, $amount, $currentPaid, $totalCost) {
            $newTotalPaid = $currentPaid + $amount;
            
            // Determine status based on payment
            $status = 'pending';
            if ($newTotalPaid >= $totalCost) {
                $status = 'paid'; // Fully paid
            } elseif ($newTotalPaid > 0) {
                $status = 'partial'; // Partially paid
            }

            // Update reservation with payment information
            $reservation->update([
                'amount_paid' => $newTotalPaid,
                'payment_method' => $validated['payment_method'],
                'status' => $status,
                'notes' => $validated['notes'] 
                    ? ($reservation->notes ? $reservation->notes . "\n" . $validated['notes'] : $validated['notes'])
                    : $reservation->notes,
            ]);

            // If there's a customer, update their amount_paid
            if ($reservation->customer) {
                $customerPaid = $reservation->customer->amount_paid ?? 0;
                $reservation->customer->update([
                    'amount_paid' => $customerPaid + $amount,
                ]);
            }
        });

        $message = $reservation->is_fully_paid 
            ? 'Payment processed successfully. Reservation is now fully paid.' 
            : "Partial payment of ₱" . number_format($amount, 2) . " processed successfully. Remaining balance: ₱" . number_format($reservation->amount_remaining, 2);

        return back()->with('success', $message);
    }

    public function processCustomerPayment(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,gcash,maya,card',
            'notes' => 'nullable|string|max:500',
        ]);

        $reservation = Reservation::findOrFail($validated['reservation_id']);
        $amount = (float) $validated['amount'];
        $totalCost = $reservation->total_cost;
        $currentPaid = (float) ($reservation->amount_paid ?? 0);
        $remaining = $totalCost - $currentPaid;

        // Validate payment amount doesn't exceed remaining balance
        if ($amount > $remaining) {
            return back()->withErrors(['amount' => "Payment amount cannot exceed remaining balance of ₱" . number_format($remaining, 2)]);
        }

        DB::transaction(function () use ($customer, $reservation, $validated, $amount, $currentPaid, $totalCost) {
            $newTotalPaid = $currentPaid + $amount;
            
            // Determine status based on payment
            $status = 'pending';
            if ($newTotalPaid >= $totalCost) {
                $status = 'paid'; // Fully paid
            } elseif ($newTotalPaid > 0) {
                $status = 'partial'; // Partially paid
            }

            $reservation->update([
                'amount_paid' => $newTotalPaid,
                'payment_method' => $validated['payment_method'],
                'status' => $status,
                'notes' => $validated['notes'] 
                    ? ($reservation->notes ? $reservation->notes . "\n" . $validated['notes'] : $validated['notes'])
                    : $reservation->notes,
            ]);

            // Update customer's total amount paid
            $customerPaid = $customer->amount_paid ?? 0;
            $customer->update([
                'amount_paid' => $customerPaid + $amount,
            ]);
        });

        $message = $reservation->is_fully_paid 
            ? 'Payment processed successfully. Reservation is now fully paid.' 
            : "Partial payment of ₱" . number_format($amount, 2) . " processed successfully. Remaining balance: ₱" . number_format($reservation->amount_remaining, 2);

        return back()->with('success', $message);
    }
}
