<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\SpaceType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CustomerViewController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();
        $reservations = [];

        if ($user) {
            $reservations = Reservation::with(['space.spaceType', 'spaceType', 'customer'])
                ->where(function ($q) use ($user) {
                    // Include reservations created by this user
                    $q->where('user_id', $user->id);
                    // Also include reservations tied to the customer's profile (e.g., admin-started open time)
                    if ($user->relationLoaded('customer') ? $user->customer : $user->customer()->exists()) {
                        $customerId = optional($user->customer)->id;
                        if ($customerId) {
                            $q->orWhere('customer_id', $customerId);
                        }
                    }
                })
                ->latest()
                ->get()
                ->map(function ($reservation) {
                    $spaceTypeName = optional(optional($reservation->space)->spaceType)->name
                        ?? optional($reservation->spaceType)->name
                        ?? 'Reserved Space';

                    return [
                        'id' => $reservation->id,
                        'service' => $spaceTypeName,
                        'date' => $reservation->created_at->toFormattedDateString(),
                        'start_time' => $reservation->start_time,
                        'end_time' => $reservation->end_time,
                        'hours' => $reservation->hours,
                        'pax' => $reservation->pax,
                        'payment_method' => $reservation->payment_method,
                        'total_cost' => $reservation->total_cost,
                        'amount_paid' => $reservation->amount_paid ?? 0,
                        'amount_remaining' => $reservation->amount_remaining,
                        'is_partially_paid' => $reservation->is_partially_paid,
                        'is_fully_paid' => $reservation->is_fully_paid,
                        'effective_hourly_rate' => $reservation->effective_hourly_rate,
                        'status' => $reservation->status,
                        'is_open_time' => (bool) $reservation->is_open_time,
                        'space_type_id' => $reservation->space_type_id,
                        'space_type' => $reservation->spaceType ? [
                            'id' => $reservation->spaceType->id,
                            'name' => $reservation->spaceType->name,
                        ] : null,
                        'customer' => $reservation->customer ? [
                            'name' => $reservation->customer->name,
                            'email' => $reservation->customer->email,
                            'phone' => $reservation->customer->phone,
                            'company_name' => $reservation->customer->company_name,
                        ] : null,
                        'customer_name' => $reservation->customer->name ?? null,
                        'customer_email' => $reservation->customer->email ?? null,
                        'customer_phone' => $reservation->customer->phone ?? null,
                        'customer_company_name' => $reservation->customer->company_name ?? null,
                    ];
                });
        }

        $spaceTypes = SpaceType::select(
                'id',
                'name',
                'description',
        'photo_path',
                'default_price',
                'hourly_rate',
                'pricing_type',
                'default_discount_hours',
                'default_discount_percentage',
                'available_slots',
                'total_slots'
            )
            ->orderBy('name')
            ->get()
            ->map(function (SpaceType $type) {
                $price = $type->hourly_rate ?? $type->default_price;

                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'slug' => Str::slug($type->name),
                    'description' => $type->description,
                    'photo_url' => $type->photo_path ? asset('storage/' . $type->photo_path) . '?v=' . urlencode(optional($type->updated_at)->timestamp ?? time()) : null,
                    'price_per_hour' => $price,
                    'pricing_type' => $type->pricing_type ?? 'per_person',
                    'discount_hours' => $type->default_discount_hours,
                    'discount_percentage' => $type->default_discount_percentage,
                    'available_slots' => $type->available_slots,
                    'total_slots' => $type->total_slots,
                ];
            });

        return Inertia::render('CustomerView/Index', [
            'spaceTypes' => $spaceTypes,
            'auth' => [
                'user' => Auth::user(),
            ],
            'reservations' => $reservations,
        ]);
    }
}
