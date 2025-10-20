<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Models\Space;
use App\Models\SpaceType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssignUnassignedReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:assign-unassigned';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically assign unassigned reservations to available physical spaces';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Finding unassigned active reservations...');

        // Get all active reservations without a space_id
        $unassignedReservations = Reservation::whereNull('space_id')
            ->whereNotNull('space_type_id')
            ->where(function($q) {
                $q->whereNull('status')
                  ->orWhereNotIn('status', ['completed', 'cancelled']);
            })
            ->with(['spaceType', 'customer'])
            ->get();

        if ($unassignedReservations->isEmpty()) {
            $this->info('No unassigned reservations found.');
            return 0;
        }

        $this->info("Found {$unassignedReservations->count()} unassigned reservation(s).");

        $assigned = 0;
        $skipped = 0;

        foreach ($unassignedReservations as $reservation) {
            DB::transaction(function () use ($reservation, &$assigned, &$skipped) {
                // Find a physical space of the same type that doesn't have a time conflict
                $availableSpace = Space::where('space_type_id', $reservation->space_type_id)
                    ->whereDoesntHave('reservations', function($q) use ($reservation) {
                        // Check for overlapping reservations (exclude this reservation if it somehow already has space_id)
                        $q->where('id', '!=', $reservation->id)
                          ->active()
                          ->where(function($sub) use ($reservation) {
                              if ($reservation->end_time) {
                                  $sub->where('start_time', '<', $reservation->end_time)
                                      ->where('end_time', '>', $reservation->start_time);
                              } else {
                                  // For open-ended reservations, just check start time
                                  $sub->where('start_time', '>=', $reservation->start_time);
                              }
                          });
                    })
                    ->first();

                if (!$availableSpace) {
                    // Debug: Count total spaces
                    $totalSpaces = Space::where('space_type_id', $reservation->space_type_id)->count();
                    
                    $this->warn("✗ No available space for Reservation #{$reservation->id} ({$reservation->spaceType->name}) - time conflict");
                    $this->line("  Total spaces of this type: $totalSpaces");
                    $skipped++;
                    return;
                }

                // Just link the reservation to the space - don't change space status
                // Status should be managed dynamically based on current time
                $reservation->update([
                    'space_id' => $availableSpace->id,
                ]);

                $this->info("✓ Assigned Reservation #{$reservation->id} to Space '{$availableSpace->name}' ({$reservation->spaceType->name})");
                $assigned++;
            });
        }

        $this->info("\n" . str_repeat('=', 50));
        $this->info("Summary:");
        $this->info("  Assigned: {$assigned}");
        $this->info("  Skipped:  {$skipped}");
        $this->info(str_repeat('=', 50));

        return 0;
    }
}
