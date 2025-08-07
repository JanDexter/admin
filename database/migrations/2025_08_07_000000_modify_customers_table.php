<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Drop old columns if they exist
            if (Schema::hasColumn('customers', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('customers', 'company')) {
                $table->dropColumn('company');
            }

            // Add new columns
            if (!Schema::hasColumn('customers', 'company_name')) {
                $table->string('company_name')->after('id');
            }
            if (!Schema::hasColumn('customers', 'contact_person')) {
                $table->string('contact_person')->after('company_name');
            }
            if (!Schema::hasColumn('customers', 'website')) {
                $table->string('website')->nullable()->after('address');
            }
            if (!Schema::hasColumn('customers', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
            if (!Schema::hasColumn('customers', 'deleted_at')) {
                $table->softDeletes();
            }

            // Modify status column
            $table->string('status')->default('active')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Revert new columns
            if (Schema::hasColumn('customers', 'company_name')) {
                $table->dropColumn('company_name');
            }
            if (Schema::hasColumn('customers', 'contact_person')) {
                $table->dropColumn('contact_person');
            }
            if (Schema::hasColumn('customers', 'website')) {
                $table->dropColumn('website');
            }
            if (Schema::hasColumn('customers', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('customers', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            // Revert old columns
            if (!Schema::hasColumn('customers', 'name')) {
                $table->string('name');
            }
            if (!Schema::hasColumn('customers', 'company')) {
                $table->string('company')->nullable();
            }

            // Revert status column change
            $table->enum('status', ['active', 'inactive'])->default('active')->change();
        });
    }
};
