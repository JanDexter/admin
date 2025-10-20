<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, temporarily change status to VARCHAR to update data safely
        DB::statement("ALTER TABLE reservations MODIFY status VARCHAR(20) NOT NULL DEFAULT 'pending'");
        
        // Update any old status values to match the new enum
        DB::statement("UPDATE reservations SET status = 'on_hold' WHERE status = 'hold'");
        DB::statement("UPDATE reservations SET status = 'pending' WHERE status NOT IN ('pending', 'on_hold', 'confirmed', 'active', 'paid', 'completed', 'cancelled')");
        
        // Now change it to the new enum with 'partial' included
        DB::statement("ALTER TABLE reservations MODIFY status ENUM('pending', 'on_hold', 'confirmed', 'active', 'paid', 'partial', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Revert to previous enum values
            $table->enum('status', [
                'pending',
                'on_hold',
                'confirmed',
                'active',
                'paid',
                'completed',
                'cancelled'
            ])->default('pending')->change();
        });
    }
};
