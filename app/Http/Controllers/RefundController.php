<?php

namespace App\Http\Controllers;

use App\Models\Refund;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RefundController extends Controller
{
    /**
     * Display a listing of refunds
     */
    public function index(Request $request)
    {
        $query = Refund::with(['reservation.customer', 'reservation.spaceType', 'processedBy'])
            ->latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('reservation.customer', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $refunds = $query->paginate(20)->through(function ($refund) {
            return [
                'id' => $refund->id,
                'reference_number' => $refund->reference_number,
                'refund_amount' => $refund->refund_amount,
                'cancellation_fee' => $refund->cancellation_fee,
                'original_amount_paid' => $refund->original_amount_paid,
                'refund_method' => $refund->refund_method,
                'status' => $refund->status,
                'reason' => $refund->reason,
                'notes' => $refund->notes,
                'processed_at' => $refund->processed_at,
                'created_at' => $refund->created_at,
                'customer_name' => $refund->reservation->customer->name ?? 'N/A',
                'customer_email' => $refund->reservation->customer->email ?? 'N/A',
                'space_type' => $refund->reservation->spaceType->name ?? 'N/A',
                'reservation_id' => $refund->reservation_id,
                'processed_by_name' => $refund->processedBy->name ?? null,
            ];
        });

        return Inertia::render('Refunds/Index', [
            'refunds' => $refunds,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    /**
     * Process/approve a refund
     */
    public function process(Request $request, Refund $refund)
    {
        if ($refund->status !== 'pending') {
            return back()->with('error', 'Only pending refunds can be processed.');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($refund, $validated) {
            $refund->update([
                'status' => 'completed',
                'processed_by' => Auth::id(),
                'processed_at' => now(),
                'notes' => isset($validated['notes']) 
                    ? ($refund->notes ? $refund->notes . "\n" . $validated['notes'] : $validated['notes'])
                    : $refund->notes,
            ]);
        });

        return back()->with('success', sprintf(
            'Refund of ₱%s processed successfully.',
            number_format($refund->refund_amount, 2)
        ));
    }

    /**
     * Reject a refund
     */
    public function reject(Request $request, Refund $refund)
    {
        if ($refund->status !== 'pending') {
            return back()->with('error', 'Only pending refunds can be rejected.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        DB::transaction(function () use ($refund, $validated) {
            $refund->update([
                'status' => 'failed',
                'processed_by' => Auth::id(),
                'processed_at' => now(),
                'notes' => ($refund->notes ? $refund->notes . "\n" : '') . 'REJECTED: ' . $validated['reason'],
            ]);
        });

        return back()->with('success', 'Refund rejected.');
    }

    /**
     * Cancel a reservation from admin panel with refund
     */
    public function cancelReservation(Request $request, Reservation $reservation)
    {
        if (in_array($reservation->status, ['cancelled', 'completed'])) {
            return back()->with('error', 'Cannot cancel a reservation that is already cancelled or completed.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
            'override_fee' => 'nullable|boolean',
        ]);

        // Calculate refund
        $refundInfo = Refund::calculateRefund($reservation);
        $refundAmount = $refundInfo['refund_amount'];
        $cancellationFee = $refundInfo['cancellation_fee'];

        // Admin can override cancellation fee for full refund
        if ($validated['override_fee'] ?? false) {
            $refundAmount = (float) $reservation->amount_paid;
            $cancellationFee = 0;
            $refundInfo['percentage'] = 100;
        }

        DB::transaction(function () use ($reservation, $refundAmount, $cancellationFee, $refundInfo, $validated) {
            // Update reservation status
            $reservation->status = 'cancelled';
            $reservation->notes = ($reservation->notes ? $reservation->notes . "\n" : '') 
                . 'ADMIN CANCELLED: ' . $validated['reason'];
            $reservation->save();

            // Create refund record if payment was made
            if ($reservation->amount_paid > 0) {
                $refund = Refund::create([
                    'reservation_id' => $reservation->id,
                    'customer_id' => $reservation->customer_id,
                    'refund_amount' => $refundAmount,
                    'original_amount_paid' => $reservation->amount_paid,
                    'cancellation_fee' => $cancellationFee,
                    'refund_method' => $reservation->payment_method,
                    'status' => 'pending',
                    'reason' => $validated['reason'],
                    'reference_number' => Refund::generateReferenceNumber(),
                    'notes' => sprintf(
                        'Admin cancelled. Refund: %d%%',
                        $refundInfo['percentage']
                    ),
                    'processed_by' => Auth::id(),
                ]);

                // Auto-approve if admin override or full refund
                if (($validated['override_fee'] ?? false) || $refundInfo['percentage'] >= 100) {
                    $refund->update([
                        'status' => 'completed',
                        'processed_at' => now(),
                    ]);
                }
            }
        });

        // Build success message
        $message = 'Reservation cancelled successfully.';
        if ($reservation->amount_paid > 0 && $refundAmount > 0) {
            $message .= sprintf(
                ' Refund of ₱%s will be processed.',
                number_format($refundAmount, 2)
            );
        }

        return back()->with('success', $message);
    }
}
