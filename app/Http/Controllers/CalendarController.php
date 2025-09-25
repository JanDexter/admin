<?php

namespace App\Http\Controllers;

use App\Models\Space;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CalendarController extends Controller
{
    public function index()
    {
        $events = [];

        $reservations = Reservation::with(['space', 'customer'])
            ->orderBy('start_time', 'asc')
            ->get();

        foreach ($reservations as $res) {
            if (!$res->space) {
                continue;
            }
            $start = $res->start_time ? $res->start_time->toIso8601String() : null;
            $end = $res->end_time ? $res->end_time->toIso8601String() : null;

            if (!$start) {
                continue; // skip malformed rows
            }

            $titleCustomer = $res->customer?->name
                ?? $res->customer?->company_name
                ?? $res->customer?->contact_person
                ?? 'Customer';

            $events[] = [
                'title' => ($res->space->name ?? 'Space') . ' — ' . $titleCustomer,
                'start' => $start,
                'end' => $end, // can be null for ongoing
                'allDay' => false,
                'extendedProps' => [
                    'space' => $res->space->name ?? 'N/A',
                    'spaceType' => $res->space->spaceType->name ?? null,
                    'customer' => $titleCustomer,
                    'contact' => $res->customer->contact_person ?? null,
                    'email' => $res->customer->email ?? null,
                    'phone' => $res->customer->phone ?? null,
                    'rate' => $res->custom_hourly_rate,
                    'cost' => $res->cost,
                    'reservationId' => $res->id,
                ],
            ];
        }

        return Inertia::render('Calendar/Index', [
            'events' => $events,
        ]);
    }
}
