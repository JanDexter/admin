<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Space;

class ReleaseExpiredSpaces extends Command
{
    protected $signature = 'spaces:release-expired';
    protected $description = 'Automatically release spaces whose occupied_until has passed.';

    public function handle(): int
    {
        $count = 0;
        $now = now();
        $spaces = Space::where('status', 'occupied')
            ->whereNotNull('occupied_until')
            ->where('occupied_until', '<=', $now)
            ->get();

        foreach ($spaces as $space) {
            $space->release();
            $count++;
            $this->info("Released space {$space->id} ({$space->name})");
        }

        $this->info("Released {$count} expired space(s).");
        return self::SUCCESS;
    }
}
